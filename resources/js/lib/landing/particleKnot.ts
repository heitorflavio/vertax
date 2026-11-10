/**
 * Arte do hero: um torus knot desenhado com milhares de partículas em WebGL.
 * O cursor empurra as partículas em 3D e uma mola as traz de volta; o nó gira
 * devagar no eixo Y. Sem dependências: matrizes e shaders escritos à mão.
 */

const FOV = (75 * Math.PI) / 180;
const CAMERA_Z = 5;
const MIN_ASPECT = 0.85;
const REPEL_RADIUS = 1.5;
const REPEL_STRENGTH = 0.01;
const SPRING = 0.001;
const DAMPING = 0.95;
const SPIN_SPEED = 0.05;
const STEP = 1 / 60;
const POINT_SIZE = 2.25;
const KNOT_REACH = 3.1;

const VERTEX_SHADER = `
attribute vec3 aPosition;
attribute vec3 aColor;
attribute float aTwinkle;
uniform mat4 uProjection;
uniform mat4 uModelView;
uniform float uTime;
uniform float uSizePx;
uniform float uCamZ;
varying vec3 vColor;

void main() {
    vColor = aColor;
    vec4 mv = uModelView * vec4(aPosition, 1.0);
    float twinkle = 0.9 + 0.1 * sin(uTime * 1.6 + aTwinkle);
    gl_PointSize = uSizePx * twinkle * (uCamZ / max(0.1, -mv.z));
    gl_Position = uProjection * mv;
}
`;

const FRAGMENT_SHADER = `
precision mediump float;
uniform float uOpacity;
uniform float uLight;
varying vec3 vColor;

void main() {
    float d = length(gl_PointCoord - 0.5);
    if (d > 0.5) discard;
    float alpha = 1.0 - smoothstep(0.14, 0.5, d);
    float core = 1.0 - smoothstep(0.0, 0.22, d);
    vec3 glow = vColor * (1.0 + core * 0.55);
    vec3 ink = vColor * (1.0 - core * 0.2);
    gl_FragColor = vec4(mix(glow, ink, uLight), alpha * uOpacity);
}
`;

export interface ParticleKnotOptions {
    light: boolean;
    reducedMotion: boolean;
}

export interface ParticleKnot {
    setLight(light: boolean): void;
    destroy(): void;
}

type Rgb = [number, number, number];

function srgbToLinear(hex: string): Rgb {
    const value = parseInt(hex.slice(1), 16);

    return [16, 8, 0].map((shift) => {
        const c = ((value >> shift) & 255) / 255;

        return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    }) as Rgb;
}

function hueChannel(p: number, q: number, t: number): number {
    if (t < 0) t += 1;
    if (t > 1) t -= 1;
    if (t < 1 / 6) return p + (q - p) * 6 * t;
    if (t < 1 / 2) return q;
    if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;

    return p;
}

function hsl(h: number, s: number, l: number): Rgb {
    const q = l <= 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;

    return [
        hueChannel(p, q, h + 1 / 3),
        hueChannel(p, q, h),
        hueChannel(p, q, h - 1 / 3),
    ];
}

/** Vértices de um torus knot (p, q), na mesma grade de anéis de um tubo. */
function torusKnot(
    radius: number,
    tube: number,
    tubular: number,
    radial: number,
    p: number,
    q: number,
): Float32Array {
    const out = new Float32Array((tubular + 1) * (radial + 1) * 3);
    const curve = (u: number): Rgb => {
        const cs = Math.cos((q / p) * u);

        return [
            radius * (2 + cs) * 0.5 * Math.cos(u),
            radius * (2 + cs) * 0.5 * Math.sin(u),
            radius * Math.sin((q / p) * u) * 0.5,
        ];
    };
    let k = 0;

    for (let i = 0; i <= tubular; i++) {
        const u = (i / tubular) * p * Math.PI * 2;
        const a = curve(u);
        const b = curve(u + 0.01);
        const t: Rgb = [b[0] - a[0], b[1] - a[1], b[2] - a[2]];
        const n: Rgb = [b[0] + a[0], b[1] + a[1], b[2] + a[2]];
        const bin: Rgb = [
            t[1] * n[2] - t[2] * n[1],
            t[2] * n[0] - t[0] * n[2],
            t[0] * n[1] - t[1] * n[0],
        ];
        const nor: Rgb = [
            bin[1] * t[2] - bin[2] * t[1],
            bin[2] * t[0] - bin[0] * t[2],
            bin[0] * t[1] - bin[1] * t[0],
        ];
        const lb = Math.hypot(...bin) || 1;
        const ln = Math.hypot(...nor) || 1;

        for (let j = 0; j <= radial; j++) {
            const v = (j / radial) * Math.PI * 2;
            const cx = -tube * Math.cos(v);
            const cy = tube * Math.sin(v);

            for (let axis = 0; axis < 3; axis++) {
                out[k++] =
                    a[axis] + (cx * nor[axis]) / ln + (cy * bin[axis]) / lb;
            }
        }
    }

    return out;
}

function perspective(out: Float32Array, aspect: number): void {
    const near = 0.1;
    const far = 1000;
    const f = 1 / Math.tan(FOV / 2);
    const nf = 1 / (near - far);

    out.fill(0);
    out[0] = f / aspect;
    out[5] = f;
    out[10] = (far + near) * nf;
    out[11] = -1;
    out[14] = 2 * far * near * nf;
}

function spinY(out: Float32Array, angle: number, distance: number): void {
    const c = Math.cos(angle);
    const s = Math.sin(angle);

    out.fill(0);
    out[0] = c;
    out[2] = -s;
    out[5] = 1;
    out[8] = s;
    out[10] = c;
    out[14] = -distance;
    out[15] = 1;
}

export function createParticleKnot(
    host: HTMLElement,
    options: ParticleKnotOptions,
): ParticleKnot | null {
    const canvas = document.createElement('canvas');
    canvas.style.display = 'block';
    canvas.style.width = '100%';
    canvas.style.height = '100%';

    const context = canvas.getContext('webgl', {
        alpha: true,
        antialias: true,
        depth: false,
        premultipliedAlpha: true,
    });

    if (!context) {
        return null;
    }

    const gl: WebGLRenderingContext = context;
    host.appendChild(canvas);

    const rest = torusKnot(1.5, 0.5, 200, 32, 2, 3);
    const vertexCount = rest.length / 3;
    const count = Math.min(
        50000,
        Math.max(
            8000,
            Math.floor(host.clientWidth * host.clientHeight * 0.025),
        ),
    );

    const home = new Float32Array(count * 3);
    const position = new Float32Array(count * 3);
    const velocity = new Float32Array(count * 3);
    const twinkle = new Float32Array(count);
    const seeds = new Float32Array(count * 3);
    let minY = Infinity;
    let maxY = -Infinity;

    for (let i = 0; i < count; i++) {
        const v = (i % vertexCount) * 3;

        for (let axis = 0; axis < 3; axis++) {
            home[i * 3 + axis] = rest[v + axis];
            seeds[i * 3 + axis] = Math.random();
        }

        minY = Math.min(minY, rest[v + 1]);
        maxY = Math.max(maxY, rest[v + 1]);
        twinkle[i] = Math.random() * Math.PI * 2;
    }

    position.set(home);

    // Azul-ciano em HSL; a base do nó puxa para o laranja da marca.
    function colors(light: boolean): Float32Array {
        const out = new Float32Array(count * 3);
        const orange = srgbToLinear(light ? '#e06a34' : '#ff8a5b');

        for (let i = 0; i < count; i++) {
            const hue = 0.53 + 0.1 * seeds[i * 3];
            const base = light
                ? hsl(hue, 0.75, 0.38 + 0.17 * seeds[i * 3 + 1])
                : hsl(hue, 0.85, 0.5 + 0.2 * seeds[i * 3 + 1]);
            const height = (home[i * 3 + 1] - minY) / (maxY - minY);
            const mix =
                height >= 0.42
                    ? 0
                    : (1 - height / 0.42) ** 1.6 *
                      (0.72 + 0.28 * seeds[i * 3 + 2]);

            for (let c = 0; c < 3; c++) {
                out[i * 3 + c] = base[c] + (orange[c] - base[c]) * mix;
            }
        }

        return out;
    }

    let light = options.light;
    let program: WebGLProgram | null = null;
    let positionBuffer: WebGLBuffer | null = null;
    let colorBuffer: WebGLBuffer | null = null;
    let twinkleBuffer: WebGLBuffer | null = null;
    let uModelView: WebGLUniformLocation | null = null;
    let uProjection: WebGLUniformLocation | null = null;
    let uTime: WebGLUniformLocation | null = null;
    let uSizePx: WebGLUniformLocation | null = null;
    let uCamZ: WebGLUniformLocation | null = null;
    let uLight: WebGLUniformLocation | null = null;

    function compile(type: number, source: string): WebGLShader | null {
        const shader = gl.createShader(type);

        if (!shader) return null;

        gl.shaderSource(shader, source);
        gl.compileShader(shader);

        if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
            gl.deleteShader(shader);

            return null;
        }

        return shader;
    }

    function attribute(
        target: WebGLProgram,
        name: string,
        data: Float32Array,
        size: number,
        usage: number,
    ): WebGLBuffer | null {
        const location = gl.getAttribLocation(target, name);
        const buffer = gl.createBuffer();

        gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
        gl.bufferData(gl.ARRAY_BUFFER, data, usage);
        gl.enableVertexAttribArray(location);
        gl.vertexAttribPointer(location, size, gl.FLOAT, false, 0, 0);

        return buffer;
    }

    function applyTheme(): void {
        if (light) {
            gl.blendFuncSeparate(
                gl.SRC_ALPHA,
                gl.ONE_MINUS_SRC_ALPHA,
                gl.ONE,
                gl.ONE_MINUS_SRC_ALPHA,
            );
        } else {
            gl.blendFunc(gl.SRC_ALPHA, gl.ONE);
        }

        gl.uniform1f(uLight, light ? 1 : 0);
    }

    function setup(): boolean {
        const vs = compile(gl.VERTEX_SHADER, VERTEX_SHADER);
        const fs = compile(gl.FRAGMENT_SHADER, FRAGMENT_SHADER);
        program = gl.createProgram();

        if (!vs || !fs || !program) return false;

        gl.attachShader(program, vs);
        gl.attachShader(program, fs);
        gl.linkProgram(program);
        gl.deleteShader(vs);
        gl.deleteShader(fs);

        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return false;

        gl.useProgram(program);
        positionBuffer = attribute(
            program,
            'aPosition',
            position,
            3,
            gl.DYNAMIC_DRAW,
        );
        colorBuffer = attribute(
            program,
            'aColor',
            colors(light),
            3,
            gl.STATIC_DRAW,
        );
        twinkleBuffer = attribute(
            program,
            'aTwinkle',
            twinkle,
            1,
            gl.STATIC_DRAW,
        );

        uModelView = gl.getUniformLocation(program, 'uModelView');
        uProjection = gl.getUniformLocation(program, 'uProjection');
        uTime = gl.getUniformLocation(program, 'uTime');
        uSizePx = gl.getUniformLocation(program, 'uSizePx');
        uCamZ = gl.getUniformLocation(program, 'uCamZ');
        uLight = gl.getUniformLocation(program, 'uLight');
        gl.uniform1f(gl.getUniformLocation(program, 'uOpacity'), 0.9);

        gl.disable(gl.DEPTH_TEST);
        gl.enable(gl.BLEND);
        gl.clearColor(0, 0, 0, 0);
        applyTheme();

        return true;
    }

    const projection = new Float32Array(16);
    const modelView = new Float32Array(16);
    let aspect = 1;
    let camZ = CAMERA_Z;

    function resize(): void {
        const width = Math.max(host.clientWidth, 1);
        const height = Math.max(host.clientHeight, 1);
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        aspect = width / height;
        // Em contêineres estreitos a câmera recua para o nó caber inteiro.
        camZ = CAMERA_Z * Math.max(1, MIN_ASPECT / aspect);
        perspective(projection, aspect);

        gl.viewport(0, 0, canvas.width, canvas.height);
        gl.uniformMatrix4fv(uProjection, false, projection);
        gl.uniform1f(uSizePx, POINT_SIZE * dpr);
        gl.uniform1f(uCamZ, camZ);
    }

    const pointer = { x: 0, y: 0, active: false };
    const cursor = { x: 0, y: 0, z: 0, near: false };
    let settled = true;

    // Cursor no plano z = 0 do mundo, levado para o espaço local do nó.
    function locateCursor(spin: number): void {
        const halfHeight = Math.tan(FOV / 2) * camZ;
        const wx = pointer.x * halfHeight * aspect;
        const wy = pointer.y * halfHeight;

        cursor.x = Math.cos(spin) * wx;
        cursor.y = wy;
        cursor.z = Math.sin(spin) * wx;
        cursor.near =
            pointer.active && Math.hypot(wx, wy) < KNOT_REACH + REPEL_RADIUS;
    }

    function step(): void {
        const repel = cursor.near;
        const mx = cursor.x;
        const my = cursor.y;
        const mz = cursor.z;
        let drift = 0;

        for (let i = 0; i < count * 3; i += 3) {
            const px = position[i];
            const py = position[i + 1];
            const pz = position[i + 2];
            let vx = velocity[i];
            let vy = velocity[i + 1];
            let vz = velocity[i + 2];

            if (repel) {
                const dx = px - mx;
                const dy = py - my;
                const dz = pz - mz;
                const d2 = dx * dx + dy * dy + dz * dz;

                if (d2 < REPEL_RADIUS * REPEL_RADIUS && d2 > 1e-8) {
                    const d = Math.sqrt(d2);
                    const force = ((REPEL_RADIUS - d) * REPEL_STRENGTH) / d;
                    vx += dx * force;
                    vy += dy * force;
                    vz += dz * force;
                }
            }

            vx = (vx + (home[i] - px) * SPRING) * DAMPING;
            vy = (vy + (home[i + 1] - py) * SPRING) * DAMPING;
            vz = (vz + (home[i + 2] - pz) * SPRING) * DAMPING;
            position[i] = px + vx;
            position[i + 1] = py + vy;
            position[i + 2] = pz + vz;
            velocity[i] = vx;
            velocity[i + 1] = vy;
            velocity[i + 2] = vz;
            drift = Math.max(
                drift,
                Math.abs(position[i] - home[i]) +
                    Math.abs(position[i + 1] - home[i + 1]) +
                    Math.abs(position[i + 2] - home[i + 2]),
            );
        }

        settled = !repel && drift < 1e-4;

        if (settled) {
            position.set(home);
            velocity.fill(0);
        }

        gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
        gl.bufferSubData(gl.ARRAY_BUFFER, 0, position);
    }

    function draw(spin: number, time: number): void {
        spinY(modelView, spin, camZ);
        gl.uniformMatrix4fv(uModelView, false, modelView);
        gl.uniform1f(uTime, time);
        gl.clear(gl.COLOR_BUFFER_BIT);
        gl.drawArrays(gl.POINTS, 0, count);
    }

    let raf = 0;
    let running = false;
    let onScreen = true;
    let lost = false;
    let elapsed = 0;
    let last = 0;
    let pending = 0;

    function frame(now: number): void {
        raf = requestAnimationFrame(frame);

        const dt = last ? Math.min((now - last) / 1000, 0.1) : 0;
        last = now;
        elapsed += dt;
        pending += dt;

        const spin = elapsed * SPIN_SPEED;
        let steps = 0;

        locateCursor(spin);

        while (pending >= STEP && steps < 4) {
            if (!settled || cursor.near) step();
            pending -= STEP;
            steps++;
        }

        if (steps === 4) pending = 0;

        draw(spin, elapsed);
    }

    function sync(): void {
        const shouldRun =
            onScreen && !document.hidden && !lost && !options.reducedMotion;

        if (shouldRun && !running) {
            running = true;
            last = 0;
            raf = requestAnimationFrame(frame);
        } else if (!shouldRun && running) {
            running = false;
            cancelAnimationFrame(raf);
        }
    }

    function drawStill(): void {
        if (!lost && program) draw(0.6, 0);
    }

    function onPointerMove(event: PointerEvent): void {
        const rect = canvas.getBoundingClientRect();

        if (rect.width === 0 || rect.height === 0) return;

        pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        pointer.y = -(((event.clientY - rect.top) / rect.height) * 2 - 1);
        pointer.active = true;
    }

    function onPointerEnd(event: PointerEvent): void {
        if (event.pointerType !== 'mouse') pointer.active = false;
    }

    function onPointerLeave(): void {
        pointer.active = false;
    }

    function onContextLost(event: Event): void {
        event.preventDefault();
        lost = true;
        sync();
    }

    function onContextRestored(): void {
        lost = !setup();

        if (!lost) {
            resize();
            gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer);
            gl.bufferSubData(gl.ARRAY_BUFFER, 0, position);
        }

        if (options.reducedMotion) drawStill();
        else sync();
    }

    if (!setup()) {
        canvas.remove();

        return null;
    }

    resize();

    const resizeObserver = new ResizeObserver(() => {
        resize();
        if (options.reducedMotion) drawStill();
    });
    resizeObserver.observe(host);

    const visibilityObserver = new IntersectionObserver(([entry]) => {
        onScreen = entry.isIntersecting;
        sync();
    });
    visibilityObserver.observe(canvas);

    canvas.addEventListener('webglcontextlost', onContextLost);
    canvas.addEventListener('webglcontextrestored', onContextRestored);
    document.addEventListener('visibilitychange', sync);

    if (options.reducedMotion) {
        drawStill();
    } else {
        window.addEventListener('pointermove', onPointerMove, {
            passive: true,
        });
        window.addEventListener('pointerdown', onPointerMove, {
            passive: true,
        });
        window.addEventListener('pointerup', onPointerEnd);
        window.addEventListener('pointercancel', onPointerEnd);
        document.documentElement.addEventListener('mouseleave', onPointerLeave);
        sync();
    }

    return {
        setLight(value: boolean): void {
            light = value;

            if (lost || !program) return;

            gl.bindBuffer(gl.ARRAY_BUFFER, colorBuffer);
            gl.bufferData(gl.ARRAY_BUFFER, colors(light), gl.STATIC_DRAW);
            applyTheme();
            if (options.reducedMotion) drawStill();
        },

        destroy(): void {
            running = false;
            cancelAnimationFrame(raf);
            resizeObserver.disconnect();
            visibilityObserver.disconnect();
            canvas.removeEventListener('webglcontextlost', onContextLost);
            canvas.removeEventListener(
                'webglcontextrestored',
                onContextRestored,
            );
            document.removeEventListener('visibilitychange', sync);
            window.removeEventListener('pointermove', onPointerMove);
            window.removeEventListener('pointerdown', onPointerMove);
            window.removeEventListener('pointerup', onPointerEnd);
            window.removeEventListener('pointercancel', onPointerEnd);
            document.documentElement.removeEventListener(
                'mouseleave',
                onPointerLeave,
            );

            if (!lost) {
                gl.deleteBuffer(positionBuffer);
                gl.deleteBuffer(colorBuffer);
                gl.deleteBuffer(twinkleBuffer);
                gl.deleteProgram(program);
            }

            gl.getExtension('WEBGL_lose_context')?.loseContext();
            canvas.remove();
        },
    };
}
