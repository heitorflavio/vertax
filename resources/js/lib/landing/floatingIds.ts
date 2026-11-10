/**
 * Nuvem de identificadores mascarados (CPF, CNPJ, e-mail...) flutuando em
 * profundidade. Tudo é gerado na hora e mascarado: não há dado de ninguém.
 */

type Kind = 'CPF' | 'CNPJ' | 'CNH' | 'E-MAIL' | 'TEL' | 'NOME';

const KINDS: Kind[] = ['CPF', 'CPF', 'CNPJ', 'CNH', 'E-MAIL', 'TEL', 'NOME'];
const NAMES = ['Aurora', 'Bento', 'Cora', 'Davi', 'Eva', 'Fábio', 'Helena'];
const AREA_CODES = ['11', '21', '31', '41', '47', '51', '61', '71', '81'];
const EMAIL_INITIALS = 'abcdefgilmnoprstv'.split('');
const NAME_INITIALS = 'ABCDLMNRST'.split('');

const PALETTE = {
    dark: {
        base: '247, 247, 248',
        blue: '91, 140, 255',
        orange: '255, 138, 91',
    },
    light: { base: '10, 37, 64', blue: '37, 99, 235', orange: '194, 65, 12' },
};

interface Label {
    text: string;
    tag: Kind;
    x: number;
    y: number;
    z: number;
    vx: number;
    age: number;
    ttl: number;
    tone: 'base' | 'blue' | 'orange';
}

export interface FloatingIds {
    setLight(light: boolean): void;
    setActive(active: boolean): void;
    destroy(): void;
}

function pick<T>(list: readonly T[]): T {
    return list[Math.floor(Math.random() * list.length)];
}

function digits(n: number): string {
    let out = '';

    for (let i = 0; i < n; i++) out += Math.floor(Math.random() * 10);

    return out;
}

function maskedValue(kind: Kind): string {
    switch (kind) {
        case 'CPF':
            return Math.random() < 0.5
                ? `***.${digits(3)}.***-${digits(2)}`
                : `${digits(3)}.***.***-${digits(2)}`;
        case 'CNPJ':
            return `${digits(2)}.***.***/0001-${digits(2)}`;
        case 'CNH':
            return `${digits(3)}******${digits(2)}`;
        case 'E-MAIL':
            return `${pick(EMAIL_INITIALS)}****@mail.test`;
        case 'TEL':
            return `+55 ${pick(AREA_CODES)} 9****-${digits(4)}`;
        case 'NOME':
            return `${pick(NAMES)} ${pick(NAME_INITIALS)}. ****`;
    }
}

export function createFloatingIds(
    host: HTMLElement,
    options: { light: boolean; reducedMotion: boolean },
): FloatingIds | null {
    const canvas = document.createElement('canvas');
    canvas.style.display = 'block';
    canvas.style.width = '100%';
    canvas.style.height = '100%';

    const context = canvas.getContext('2d');

    if (!context) return null;

    const ctx: CanvasRenderingContext2D = context;
    host.appendChild(canvas);

    let colors = options.light ? PALETTE.light : PALETTE.dark;
    let width = 1;
    let height = 1;
    let labels: Label[] = [];

    function spawn(label?: Label): Label {
        const z = Math.random() ** 2;
        const roll = Math.random();
        const kind = pick(KINDS);
        const next: Label = label ?? ({} as Label);

        next.tag = kind;
        next.text = maskedValue(kind);
        next.x = Math.random() * width * 1.05 - width * 0.05;
        next.y = height * (0.08 + Math.random() * 0.84);
        next.z = z;
        next.vx = (Math.random() < 0.5 ? -1 : 1) * (0.06 + 0.22 * z);
        next.age = 0;
        next.ttl = 6 + Math.random() * 9;
        next.tone = roll < 0.07 ? 'orange' : roll < 0.15 ? 'blue' : 'base';

        return next;
    }

    function resize(): void {
        width = Math.max(host.clientWidth, 1);
        height = Math.max(host.clientHeight, 1);

        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const wanted = Math.round((width * height) / 11000);

        while (labels.length < wanted) {
            const label = spawn();
            label.age = Math.random() * label.ttl;
            labels.push(label);
        }

        labels.length = Math.min(labels.length, wanted);
        labels.sort((a, b) => a.z - b.z);
    }

    function draw(dt: number): void {
        ctx.clearRect(0, 0, width, height);
        ctx.textBaseline = 'middle';

        for (const label of labels) {
            label.age += dt;
            label.x += label.vx * dt * 60;

            if (
                label.age > label.ttl ||
                label.x < -width * 0.2 ||
                label.x > width * 1.05
            ) {
                spawn(label);
            }

            const fade = Math.min(
                1,
                label.age / 1.2,
                (label.ttl - label.age) / 1.2,
            );
            const alpha = (0.1 + 0.8 * label.z ** 1.2) * Math.max(fade, 0);
            const size = Math.round(9 + label.z * 13);
            const rgb = colors[label.tone];
            const strong = label.tone !== 'base' && label.z > 0.35;

            ctx.font = `${strong ? 500 : 400} ${size}px "Geist Mono", ui-monospace, monospace`;
            ctx.fillStyle = `rgba(${rgb}, ${alpha})`;
            ctx.fillText(label.text, label.x, label.y);

            const offset = ctx.measureText(label.text).width + size * 0.5;
            ctx.fillStyle = `rgba(${rgb}, ${alpha * 0.55})`;
            ctx.fillText(`[${label.tag}]`, label.x + offset, label.y);
        }
    }

    let raf = 0;
    let last = 0;
    let running = false;
    let active = true;
    let onScreen = false;

    function frame(now: number): void {
        raf = requestAnimationFrame(frame);
        const dt = last ? Math.min((now - last) / 1000, 0.1) : 0;
        last = now;
        draw(dt);
    }

    function sync(): void {
        const shouldRun =
            active && onScreen && !document.hidden && !options.reducedMotion;

        if (shouldRun && !running) {
            running = true;
            last = 0;
            raf = requestAnimationFrame(frame);
        } else if (!shouldRun && running) {
            running = false;
            cancelAnimationFrame(raf);
        }
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
    document.addEventListener('visibilitychange', sync);

    return {
        setLight(light: boolean): void {
            colors = light ? PALETTE.light : PALETTE.dark;
            draw(0);
        },

        setActive(value: boolean): void {
            active = value;
            sync();
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
