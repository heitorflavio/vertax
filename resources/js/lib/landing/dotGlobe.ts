/**
 * Globo de pontos em canvas 2D, centrado no Brasil: dá para arrastar, ele
 * volta sozinho para o Brasil e liga as capitais com arcos de luz.
 */
import {
    BRAZIL_BITS,
    GRID_LAT_MIN,
    GRID_STEP,
    LAND_BITS,
    ROW_COUNTS,
} from './globeData';

const DEG = Math.PI / 180;
const HOME_YAW = -52 * DEG;
const HOME_PITCH = -12 * DEG;
const ALPHA_BUCKETS = 8;

// Capitais dos 27 estados (latitude, longitude).
const CAPITALS: Array<[number, number]> = [
    [-9.97, -67.81],
    [-9.67, -35.74],
    [0.03, -51.07],
    [-3.12, -60.02],
    [-12.97, -38.5],
    [-3.73, -38.52],
    [-15.79, -47.88],
    [-20.32, -40.34],
    [-16.68, -49.25],
    [-2.53, -44.3],
    [-15.6, -56.1],
    [-20.44, -54.65],
    [-19.92, -43.94],
    [-1.46, -48.49],
    [-7.12, -34.86],
    [-25.43, -49.27],
    [-8.05, -34.88],
    [-5.09, -42.8],
    [-22.91, -43.17],
    [-5.79, -35.21],
    [-30.03, -51.23],
    [-8.76, -63.9],
    [2.82, -60.67],
    [-27.6, -48.55],
    [-23.55, -46.63],
    [-10.91, -37.07],
    [-10.18, -48.33],
];

const PALETTE = {
    dark: {
        land: '229, 231, 235',
        brazil: '91, 140, 255',
        marker: '255, 138, 91',
        rim: '255, 255, 255',
        glow: '62, 139, 255',
    },
    light: {
        land: '10, 37, 64',
        brazil: '37, 99, 235',
        marker: '224, 106, 52',
        rim: '10, 37, 64',
        glow: '37, 99, 235',
    },
};

type Vec3 = [number, number, number];

interface Arc {
    from: Vec3;
    to: Vec3;
    angle: number;
    t: number;
}

interface Ring {
    at: Vec3;
    t: number;
}

export interface DotGlobe {
    setLight(light: boolean): void;
    destroy(): void;
}

function toVec(lat: number, lon: number): Vec3 {
    return [
        Math.cos(lat * DEG) * Math.sin(lon * DEG),
        Math.sin(lat * DEG),
        Math.cos(lat * DEG) * Math.cos(lon * DEG),
    ];
}

function decode(bits: string): Uint8Array {
    const raw = atob(bits);
    const out = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);

    return out;
}

function landDots(): { points: Float32Array; brazil: Uint8Array } {
    const land = decode(LAND_BITS);
    const brazil = decode(BRAZIL_BITS);
    const points: number[] = [];
    const flags: number[] = [];
    let index = 0;

    ROW_COUNTS.forEach((count, row) => {
        const lat = GRID_LAT_MIN + row * GRID_STEP;

        for (let i = 0; i < count; i++, index++) {
            if (!((land[index >> 3] >> (index & 7)) & 1)) continue;

            points.push(...toVec(lat, -180 + ((i + 0.5) * 360) / count));
            flags.push((brazil[index >> 3] >> (index & 7)) & 1);
        }
    });

    return { points: new Float32Array(points), brazil: Uint8Array.from(flags) };
}

export function createDotGlobe(
    host: HTMLElement,
    options: { light: boolean; reducedMotion: boolean },
): DotGlobe | null {
    const canvas = document.createElement('canvas');
    canvas.style.display = 'block';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.cursor = 'grab';
    canvas.style.touchAction = 'pan-y';

    const context = canvas.getContext('2d');

    if (!context) return null;

    const ctx: CanvasRenderingContext2D = context;
    host.appendChild(canvas);

    const { points, brazil } = landDots();
    const total = brazil.length;
    const capitals = CAPITALS.map(([lat, lon]) => toVec(lat, lon));
    const projected = new Float32Array(total * 3);
    const buckets = Array.from(
        { length: ALPHA_BUCKETS * 2 },
        () => new Int32Array(total),
    );
    const bucketSizes = new Int32Array(ALPHA_BUCKETS * 2);

    let colors = options.light ? PALETTE.light : PALETTE.dark;
    let width = 1;
    let height = 1;
    let radius = 1;
    let yaw = HOME_YAW;
    let pitch = HOME_PITCH;
    let dragYaw = 0;
    let dragPitch = 0;
    let velYaw = 0;
    let velPitch = 0;
    let dragging = false;
    let lastX = 0;
    let lastY = 0;
    let idleAt = 0;
    let clock = 0;
    let nextArc = 0.6;
    let nextRing = 0.3;
    const arcs: Arc[] = [];
    const rings: Ring[] = [];

    let cy = 1;
    let sy = 0;
    let cp = 1;
    let sp = 0;

    function project(v: Vec3): Vec3 {
        const [x, y, z] = v;
        const x1 = x * cy - z * sy;
        const z1 = x * sy + z * cy;

        return [x1, y * cp - z1 * sp, y * sp + z1 * cp];
    }

    function resize(): void {
        width = Math.max(host.clientWidth, 1);
        height = Math.max(host.clientHeight, 1);
        radius = Math.min(width, height) * 0.43;

        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function spawnArc(): void {
        const front = capitals.filter((c) => project(c)[2] > 0.25);

        if (front.length < 2) return;

        const from = pick(front);
        const to = pick(front);
        const angle = Math.acos(
            Math.min(1, from[0] * to[0] + from[1] * to[1] + from[2] * to[2]),
        );

        if (angle > 6 * DEG) arcs.push({ from, to, angle, t: 0 });
    }

    function pick<T>(list: T[]): T {
        return list[Math.floor(Math.random() * list.length)];
    }

    function arcPoint(arc: Arc, t: number): Vec3 {
        const s = Math.sin(arc.angle) || 1;
        const a = Math.sin((1 - t) * arc.angle) / s;
        const b = Math.sin(t * arc.angle) / s;
        const lift = 1 + Math.sin(Math.PI * t) * (0.06 + arc.angle * 0.35);

        return project([
            (arc.from[0] * a + arc.to[0] * b) * lift,
            (arc.from[1] * a + arc.to[1] * b) * lift,
            (arc.from[2] * a + arc.to[2] * b) * lift,
        ]);
    }

    function draw(dt: number): void {
        clock += dt;

        if (!dragging) {
            dragYaw += velYaw;
            dragPitch += velPitch;
            velYaw *= 0.94;
            velPitch *= 0.94;

            if (clock - idleAt > 2.5) {
                dragYaw *= 0.985;
                dragPitch *= 0.985;
            }
        }

        const sway = options.reducedMotion ? 0 : Math.sin(clock * 0.11) * 0.45;
        yaw = HOME_YAW + sway + dragYaw;
        pitch = Math.max(-1, Math.min(1, HOME_PITCH + dragPitch));
        cy = Math.cos(yaw);
        sy = Math.sin(yaw);
        cp = Math.cos(pitch);
        sp = Math.sin(pitch);

        const cx = width / 2;
        const cyScreen = height / 2;
        const scale = radius / 230;

        ctx.clearRect(0, 0, width, height);

        const halo = ctx.createRadialGradient(
            cx,
            cyScreen,
            radius * 0.85,
            cx,
            cyScreen,
            radius * 1.35,
        );
        halo.addColorStop(0, `rgba(${colors.glow}, 0.14)`);
        halo.addColorStop(1, `rgba(${colors.glow}, 0)`);
        ctx.fillStyle = halo;
        ctx.fillRect(0, 0, width, height);

        ctx.beginPath();
        ctx.arc(cx, cyScreen, radius, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${colors.rim}, 0.02)`;
        ctx.fill();
        ctx.strokeStyle = `rgba(${colors.rim}, 0.1)`;
        ctx.lineWidth = 1;
        ctx.stroke();

        bucketSizes.fill(0);

        for (let i = 0; i < total; i++) {
            const px = points[i * 3];
            const py = points[i * 3 + 1];
            const pz = points[i * 3 + 2];
            const x = px * cy - pz * sy;
            const z1 = px * sy + pz * cy;
            const y = py * cp - z1 * sp;
            const z = py * sp + z1 * cp;
            projected[i * 3] = cx + x * radius;
            projected[i * 3 + 1] = cyScreen - y * radius;
            projected[i * 3 + 2] = z;

            if (z < -0.3) continue;

            const level =
                z < 0
                    ? 0
                    : Math.min(
                          ALPHA_BUCKETS - 1,
                          1 + Math.floor(z * (ALPHA_BUCKETS - 1)),
                      );
            const bucket = level + (brazil[i] ? ALPHA_BUCKETS : 0);
            buckets[bucket][bucketSizes[bucket]++] = i;
        }

        for (let bucket = 0; bucket < ALPHA_BUCKETS * 2; bucket++) {
            const size = bucketSizes[bucket];

            if (!size) continue;

            const level = bucket % ALPHA_BUCKETS;
            const isBrazil = bucket >= ALPHA_BUCKETS;
            const depth = level / (ALPHA_BUCKETS - 1);
            const alpha =
                level === 0
                    ? 0.05
                    : (isBrazil ? 0.35 : 0.14) +
                      (isBrazil ? 0.65 : 0.66) * depth ** 0.8;

            ctx.beginPath();

            for (let k = 0; k < size; k++) {
                const i = buckets[bucket][k];
                const z = projected[i * 3 + 2];
                const r =
                    Math.max(0.35, (0.5 + 0.75 * Math.max(z, 0)) * scale) *
                    (isBrazil ? 1.18 : 1);

                ctx.moveTo(projected[i * 3] + r, projected[i * 3 + 1]);
                ctx.arc(
                    projected[i * 3],
                    projected[i * 3 + 1],
                    r,
                    0,
                    Math.PI * 2,
                );
            }

            ctx.fillStyle = `rgba(${isBrazil ? colors.brazil : colors.land}, ${alpha})`;
            ctx.fill();
        }

        if (!options.reducedMotion) {
            nextArc -= dt;
            nextRing -= dt;

            if (nextArc <= 0 && arcs.length < 4) {
                spawnArc();
                nextArc = 0.9 + Math.random() * 0.8;
            }

            if (nextRing <= 0) {
                rings.push({ at: pick(capitals), t: 0 });
                nextRing = 0.7 + Math.random() * 0.6;
            }
        }

        ctx.lineCap = 'round';

        for (let a = arcs.length - 1; a >= 0; a--) {
            const arc = arcs[a];
            arc.t += dt / 1.9;

            if (arc.t >= 1.35) {
                arcs.splice(a, 1);
                continue;
            }

            const head = Math.min(arc.t, 1);
            const tail = Math.max(0, arc.t - 0.35);
            const fade = arc.t > 1 ? 1 - (arc.t - 1) / 0.35 : 1;
            const steps = 24;
            let prev: Vec3 | null = null;

            for (let s = 0; s <= steps; s++) {
                const t = tail + ((head - tail) * s) / steps;
                const p = arcPoint(arc, t);

                if (prev && p[2] > -0.05 && prev[2] > -0.05) {
                    ctx.beginPath();
                    ctx.moveTo(
                        cx + prev[0] * radius,
                        cyScreen - prev[1] * radius,
                    );
                    ctx.lineTo(cx + p[0] * radius, cyScreen - p[1] * radius);
                    ctx.strokeStyle = `rgba(${colors.brazil}, ${(s / steps) * 0.9 * fade})`;
                    ctx.lineWidth = 1.4;
                    ctx.stroke();
                }

                prev = p;
            }

            if (arc.t <= 1 && prev && prev[2] > 0) {
                ctx.beginPath();
                ctx.arc(
                    cx + prev[0] * radius,
                    cyScreen - prev[1] * radius,
                    2.2,
                    0,
                    Math.PI * 2,
                );
                ctx.fillStyle = `rgba(${colors.brazil}, ${fade})`;
                ctx.fill();
            }
        }

        for (let r = rings.length - 1; r >= 0; r--) {
            const ring = rings[r];
            ring.t += dt / 1.6;

            if (ring.t >= 1) {
                rings.splice(r, 1);
                continue;
            }

            const [x, y, z] = project(ring.at);

            if (z <= 0.15) continue;

            ctx.beginPath();
            ctx.arc(
                cx + x * radius,
                cyScreen - y * radius,
                2 + ring.t * 16 * scale,
                0,
                Math.PI * 2,
            );
            ctx.strokeStyle = `rgba(${colors.marker}, ${(1 - ring.t) * 0.7 * z})`;
            ctx.lineWidth = 1;
            ctx.stroke();
        }

        for (const capital of capitals) {
            const [x, y, z] = project(capital);

            if (z <= 0.08) continue;

            const px = cx + x * radius;
            const py = cyScreen - y * radius;
            const glow = ctx.createRadialGradient(px, py, 0, px, py, 7 * scale);
            glow.addColorStop(0, `rgba(${colors.marker}, ${0.45 * z})`);
            glow.addColorStop(1, `rgba(${colors.marker}, 0)`);
            ctx.fillStyle = glow;
            ctx.beginPath();
            ctx.arc(px, py, 7 * scale, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.arc(px, py, Math.max(1.4, 2.2 * scale), 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${colors.marker}, ${0.55 + 0.45 * z})`;
            ctx.fill();
        }
    }

    let raf = 0;
    let last = 0;
    let running = false;
    let onScreen = false;

    function frame(now: number): void {
        raf = requestAnimationFrame(frame);
        const dt = last ? Math.min((now - last) / 1000, 0.1) : 0;
        last = now;
        draw(dt);
    }

    function sync(): void {
        const animate = !options.reducedMotion || dragging;
        const shouldRun = onScreen && !document.hidden && animate;

        if (shouldRun && !running) {
            running = true;
            last = 0;
            raf = requestAnimationFrame(frame);
        } else if (!shouldRun && running) {
            running = false;
            cancelAnimationFrame(raf);
        }
    }

    function onPointerDown(event: PointerEvent): void {
        dragging = true;
        lastX = event.clientX;
        lastY = event.clientY;
        velYaw = 0;
        velPitch = 0;
        canvas.setPointerCapture(event.pointerId);
        canvas.style.cursor = 'grabbing';
        sync();
    }

    function onPointerMove(event: PointerEvent): void {
        if (!dragging) return;

        const k = 1.6 / Math.max(radius, 1);
        velYaw = -(event.clientX - lastX) * k;
        velPitch = (event.clientY - lastY) * k;
        dragYaw += velYaw;
        dragPitch = Math.max(-1.1, Math.min(1.1, dragPitch + velPitch));
        lastX = event.clientX;
        lastY = event.clientY;
    }

    function onPointerUp(): void {
        if (!dragging) return;

        dragging = false;
        idleAt = clock;
        canvas.style.cursor = 'grab';
        sync();
        if (options.reducedMotion) draw(0);
    }

    resize();
    draw(0);

    const resizeObserver = new ResizeObserver(() => {
        resize();
        draw(0);
    });
    resizeObserver.observe(host);

    const visibilityObserver = new IntersectionObserver(([entry]) => {
        onScreen = entry.isIntersecting;
        sync();
    });
    visibilityObserver.observe(canvas);

    canvas.addEventListener('pointerdown', onPointerDown);
    canvas.addEventListener('pointermove', onPointerMove);
    canvas.addEventListener('pointerup', onPointerUp);
    canvas.addEventListener('pointercancel', onPointerUp);
    document.addEventListener('visibilitychange', sync);

    return {
        setLight(light: boolean): void {
            colors = light ? PALETTE.light : PALETTE.dark;
            draw(0);
        },

        destroy(): void {
            cancelAnimationFrame(raf);
            resizeObserver.disconnect();
            visibilityObserver.disconnect();
            document.removeEventListener('visibilitychange', sync);
            canvas.remove();
        },
    };
}
