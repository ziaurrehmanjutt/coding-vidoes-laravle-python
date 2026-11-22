// Draws a studio project on a canvas. The rules match python_services/video_service/studio.py,
// so the live preview looks like the rendered video. Keep both files in step.

import syntax from './syntax.json';

export const WIDTH = 1080;
export const HEIGHT = 1920;
export const ENTER_SECONDS = 0.4;
export const EXIT_SECONDS = 0.3;
export const DRAWABLE = ['text', 'code', 'rect', 'ellipse', 'arrow', 'image'];

// Uploaded pictures are loaded once. The editor says where to find them and what to do when one arrives.
const pictures = new Map();
let assetUrl = () => null;
let onPictureLoad = () => {};

export function configureAssets(urlFor, onLoad) {
    assetUrl = urlFor;
    onPictureLoad = onLoad;
}

function pictureFor(obj) {
    const url = assetUrl(obj);
    if (!url) return null;
    if (!pictures.has(url)) {
        const picture = new Image();
        picture.onload = () => onPictureLoad();
        picture.src = url;
        pictures.set(url, picture);
    }
    const picture = pictures.get(url);
    return picture.complete && picture.naturalWidth ? picture : null;
}

export const num = (value, fallback = 0, low = -1e6, high = 1e6) => {
    const result = Number.parseFloat(value);
    return Number.isNaN(result) ? fallback : Math.min(high, Math.max(low, result));
};

export function ease(name, x) {
    x = Math.min(1, Math.max(0, x));
    if (name === 'ease-in') return x ** 3;
    if (name === 'ease-out') return 1 - (1 - x) ** 3;
    if (name === 'ease-in-out') return x < 0.5 ? 4 * x ** 3 : 1 - (-2 * x + 2) ** 3 / 2;
    if (name === 'back') return 1 + 2.70158 * (x - 1) ** 3 + 1.70158 * (x - 1) ** 2;
    if (name === 'bounce') {
        const n1 = 7.5625;
        const d1 = 2.75;
        if (x < 1 / d1) return n1 * x * x;
        if (x < 2 / d1) { x -= 1.5 / d1; return n1 * x * x + 0.75; }
        if (x < 2.5 / d1) { x -= 2.25 / d1; return n1 * x * x + 0.9375; }
        x -= 2.625 / d1;
        return n1 * x * x + 0.984375;
    }
    return x;
}

export function trackValue(keys, local, fallback) {
    const points = (keys || [])
        .map((key) => ({ t: num(key.t, 0, 0, 120), v: num(key.v, fallback), ease: key.ease }))
        .sort((a, b) => a.t - b.t);
    if (!points.length) return fallback;
    if (local <= points[0].t) return points[0].v;
    for (let i = 0; i < points.length - 1; i++) {
        const [a, b] = [points[i], points[i + 1]];
        if (local < b.t) return a.v + (b.v - a.v) * ease(a.ease, (local - a.t) / Math.max(1e-6, b.t - a.t));
    }
    return points[points.length - 1].v;
}

export const ANIMATED = { x: 0, y: 0, x2: 0, y2: 0, scale: 1, rotation: 0, opacity: 1 };

/** Where the object is and how it looks at `time`, or null while it is not on screen. */
export function stateAt(obj, time) {
    const start = num(obj.start, 0, 0, 120);
    const duration = num(obj.duration, 1, 0.1, 120);
    const local = time - start;
    if (local < 0 || local >= duration) return null;

    const state = { local };
    for (const [prop, fallback] of Object.entries(ANIMATED)) {
        const base = num(obj[prop], fallback);
        const keys = obj.tracks && obj.tracks[prop];
        state[prop] = keys && keys.length ? trackValue(keys, local, base) : base;
    }

    const progress = ease('ease-out', local / ENTER_SECONDS);
    if (obj.enter === 'fade') {
        state.opacity *= progress;
    } else if (obj.enter === 'pop') {
        state.opacity *= Math.min(1, progress * 2);
        state.scale *= ease('back', local / ENTER_SECONDS);
    } else if (obj.enter === 'slide') {
        state.opacity *= progress;
        state.y += (1 - progress) * 80;
        state.y2 += (1 - progress) * 80;
    }
    if (obj.exit === 'fade') state.opacity *= Math.min(1, Math.max(0, (duration - local) / EXIT_SECONDS));
    return state;
}

// ------------------------------------------------------------ text helpers

export function fontFor(obj, size) {
    const bold = obj.bold !== false;
    if (obj.font === 'mono') return `${bold ? 700 : 400} ${size}px "JetBrains Mono", monospace`;
    return `${obj.italic ? 'italic ' : ''}${bold ? 700 : 500} ${size}px Poppins, sans-serif`;
}

export function wrap(ctx, text, maxWidth) {
    const lines = [];
    for (const paragraph of text.split('\n')) {
        let line = '';
        for (const word of paragraph.split(' ')) {
            const trial = line ? `${line} ${word}` : word;
            if (line && ctx.measureText(trial).width > maxWidth) {
                lines.push(line);
                line = word;
            } else {
                line = trial;
            }
        }
        lines.push(line);
    }
    return lines;
}

function typedLines(lines, count) {
    if (count === null) return lines;
    const shown = [];
    let remaining = count;
    for (const line of lines) {
        if (remaining < 0) break;
        shown.push(line.slice(0, Math.max(0, remaining)));
        remaining -= line.length + 1;
    }
    return shown;
}

// ------------------------------------------------------------ syntax colours

const patterns = {};

function tokenPattern(key) {
    if (!patterns[key]) {
        const language = syntax.languages[key] || syntax.languages.python;
        patterns[key] = new RegExp(
            `(?<comment>${language.comment})|(?<string>${language.string})|(?<number>\\b\\d+(?:\\.\\d+)?\\b)|(?<name>[A-Za-z_]\\w*)|(?<other>\\s|.)`,
            'g',
        );
    }
    return new RegExp(patterns[key].source, 'g');
}

export function highlight(code, languageKey, colors) {
    const key = syntax.languages[languageKey] ? languageKey : 'python';
    const language = syntax.languages[key];
    const keywords = new Set(language.keywords);
    const builtins = new Set(language.builtins);
    const lines = [[]];

    for (const match of code.matchAll(tokenPattern(key))) {
        const text = match[0];
        const kind = Object.entries(match.groups).find(([, value]) => value !== undefined)[0];
        let color = colors.default;

        if (kind === 'comment') color = colors.comment;
        else if (kind === 'string') color = colors.string;
        else if (kind === 'number') color = colors.number;
        else if (kind === 'name') {
            const following = code.slice(match.index + text.length).replace(/^ +/, '').slice(0, 1);
            const word = language.ignoreCase ? text.toLowerCase() : text;
            if (keywords.has(word)) color = colors.keyword;
            else if (following === '(' || builtins.has(text)) color = colors.call;
        }

        text.split('\n').forEach((piece, index) => {
            if (index > 0) lines.push([]);
            if (piece) lines[lines.length - 1].push([piece, color]);
        });
    }
    return lines;
}

const mix = (foreground, background, amount) => {
    const parse = (hex) => [1, 3, 5].map((i) => Number.parseInt(hex.slice(i, i + 2), 16));
    const [f, b] = [parse(foreground), parse(background)];
    return `rgb(${f.map((value, i) => Math.round(value * amount + b[i] * (1 - amount))).join(',')})`;
};

// ------------------------------------------------------------ drawing

function drawText(ctx, obj, width, height, local) {
    const padding = num(obj.padding, obj.fill ? 20 : 0, 0, 400);
    if (obj.fill) {
        ctx.fillStyle = obj.fill;
        ctx.beginPath();
        ctx.roundRect(0, 0, width, height, num(obj.radius, 24, 0, 500));
        ctx.fill();
    }

    const size = num(obj.size, 64, 8, 400);
    ctx.font = fontFor(obj, size);
    ctx.textBaseline = 'alphabetic';
    const lines = wrap(ctx, String(obj.text || '').slice(0, 2000), width - 2 * padding);
    const count = obj.reveal === 'type' ? Math.floor(local * num(obj.cps, 14, 1, 200)) : null;
    const shown = typedLines(lines, count);

    const lineHeight = size * 1.3;
    const top = obj.valign === 'middle' ? Math.max(padding, (height - lineHeight * lines.length) / 2) : padding;
    const align = obj.align || 'left';
    ctx.textAlign = align === 'center' ? 'center' : align === 'right' ? 'right' : 'left';
    ctx.fillStyle = obj.color || '#ffffff';
    const anchorX = align === 'center' ? width / 2 : align === 'right' ? width - padding : padding;

    shown.forEach((line, index) => ctx.fillText(line, anchorX, top + index * lineHeight + size));
}

function drawCode(ctx, obj, width, height, local) {
    const theme = syntax.themes[obj.theme] || syntax.themes.vscode;
    const language = syntax.languages[obj.language] || syntax.languages.python;
    const radius = 28;
    const bar = 64;

    ctx.fillStyle = theme.codeCard;
    ctx.beginPath();
    ctx.roundRect(0, 0, width, height, radius);
    ctx.fill();
    ctx.save();
    ctx.clip();
    ctx.fillStyle = mix('#ffffff', theme.codeCard, 0.06);
    ctx.fillRect(0, 0, width, bar);
    ctx.restore();

    ['#ff5f56', '#ffbd2e', '#27c93f'].forEach((dot, index) => {
        ctx.fillStyle = dot;
        ctx.beginPath();
        ctx.arc(36 + index * 34, bar / 2, 10, 0, Math.PI * 2);
        ctx.fill();
    });
    ctx.font = '500 26px Poppins, sans-serif';
    ctx.fillStyle = theme.muted;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(language.filename, width / 2, bar / 2);

    const size = num(obj.size, 36, 12, 120);
    ctx.font = `400 ${size}px "JetBrains Mono", monospace`;
    ctx.textBaseline = 'alphabetic';
    const lineHeight = Math.floor(size * 1.5);
    const gutter = Math.floor(ctx.measureText('00').width) + 56;
    const code = String(obj.code || '').slice(0, 4000).replaceAll('\t', '    ').replace(/^\n+|\n+$/g, '');
    const lines = highlight(code, obj.language || 'python', theme.code);

    let remaining = obj.typing === false ? 1e9 : Math.floor(local * num(obj.cps, 20, 1, 400));
    const top = bar + 28;
    let cursor = null;

    for (let index = 0; index < lines.length; index++) {
        const baseline = top + index * lineHeight + size;
        if (remaining < 0) break;
        ctx.textAlign = 'right';
        ctx.fillStyle = mix(theme.muted, theme.codeCard, 0.5);
        ctx.fillText(String(index + 1), gutter - 24, baseline);
        ctx.textAlign = 'left';

        let x = gutter;
        for (const [text, color] of lines[index]) {
            const take = text.slice(0, Math.max(0, remaining));
            if (take) {
                ctx.fillStyle = color;
                ctx.fillText(take, x, baseline);
                x += ctx.measureText(take).width;
            }
            remaining -= text.length;
            if (remaining < 0) break;
        }
        cursor = [x, baseline];
        remaining -= 1;
    }

    if (obj.cursor !== false && cursor && Math.floor(local * 2) % 2 === 0) {
        ctx.fillStyle = theme.accent;
        ctx.fillRect(cursor[0] + 2, cursor[1] - size * 0.95, 3, size * 1.15);
    }
}

function drawPicture(ctx, obj, width, height) {
    const picture = pictureFor(obj);
    if (!picture) return;
    const radius = num(obj.radius, 0, 0, 2000);
    if (radius) {
        ctx.beginPath();
        ctx.roundRect(0, 0, width, height, Math.min(radius, Math.min(width, height) / 2));
        ctx.clip();
    }
    ctx.drawImage(picture, 0, 0, width, height);
}

function drawShape(ctx, obj, width, height) {
    const strokeWidth = num(obj.strokeWidth, 0, 0, 80);
    const inset = strokeWidth / 2;
    ctx.beginPath();
    if (obj.type === 'ellipse') {
        ctx.ellipse(width / 2, height / 2, width / 2 - inset, height / 2 - inset, 0, 0, Math.PI * 2);
    } else {
        ctx.roundRect(inset, inset, width - strokeWidth, height - strokeWidth, num(obj.radius, 0, 0, 1000));
    }
    if (obj.fill) { ctx.fillStyle = obj.fill; ctx.fill(); }
    if (strokeWidth) { ctx.lineWidth = strokeWidth; ctx.strokeStyle = obj.stroke || '#ffffff'; ctx.stroke(); }
}

/** Bounding box of an arrow, matching the Python side. */
export function arrowBox(state, obj) {
    const strokeWidth = num(obj.strokeWidth, 10, 1, 80);
    const head = obj.head === false ? 0 : Math.max(strokeWidth * 3.2, 28);
    const pad = Math.ceil(Math.max(head, strokeWidth) + 4);
    const left = Math.floor(Math.min(state.x, state.x2) - pad);
    const top = Math.floor(Math.min(state.y, state.y2) - pad);
    const right = Math.ceil(Math.max(state.x, state.x2) + pad);
    const bottom = Math.ceil(Math.max(state.y, state.y2) + pad);
    return { left, top, width: Math.max(1, right - left), height: Math.max(1, bottom - top), strokeWidth, head };
}

function drawArrow(ctx, obj, state, box) {
    const paint = obj.stroke || '#ffffff';
    const [x1, y1] = [state.x - box.left, state.y - box.top];
    let [x2, y2] = [state.x2 - box.left, state.y2 - box.top];
    const drawing = num(obj.draw, 0.6, 0, 20);
    const progress = drawing ? ease('ease-out', state.local / drawing) : 1;
    x2 = x1 + (x2 - x1) * progress;
    y2 = y1 + (y2 - y1) * progress;

    const length = Math.hypot(x2 - x1, y2 - y1);
    if (length < 1) return;
    const [ux, uy] = [(x2 - x1) / length, (y2 - y1) / length];
    const end = box.head ? [x2 - ux * box.head * 0.8, y2 - uy * box.head * 0.8] : [x2, y2];

    ctx.strokeStyle = paint;
    ctx.fillStyle = paint;
    ctx.lineWidth = box.strokeWidth;
    ctx.beginPath();
    ctx.moveTo(x1, y1);
    ctx.lineTo(end[0], end[1]);
    ctx.stroke();

    if (box.head) {
        ctx.beginPath();
        ctx.arc(x1, y1, box.strokeWidth / 2, 0, Math.PI * 2);
        ctx.fill();
        const half = box.head * 0.5;
        ctx.beginPath();
        ctx.moveTo(x2, y2);
        ctx.lineTo(x2 - ux * box.head - uy * half, y2 - uy * box.head + ux * half);
        ctx.lineTo(x2 - ux * box.head + uy * half, y2 - uy * box.head - ux * half);
        ctx.closePath();
        ctx.fill();
    }
}

export function objectSize(obj) {
    return [Math.floor(num(obj.w, 400, 1, 4000)), Math.floor(num(obj.h, 200, 1, 4000))];
}

/** The centre of an object on the canvas, used for drawing and for the selection box. */
export function objectBox(obj, state) {
    if (obj.type === 'arrow') {
        const box = arrowBox(state, obj);
        return { left: box.left, top: box.top, width: box.width, height: box.height, arrow: box };
    }
    const [width, height] = objectSize(obj);
    return { left: state.x, top: state.y, width, height };
}

export function drawFrame(ctx, document, time) {
    const top = document.background || '#0f172a';
    const gradient = ctx.createLinearGradient(0, 0, 0, HEIGHT);
    gradient.addColorStop(0, top);
    gradient.addColorStop(1, document.background2 || top);
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    for (const obj of document.objects || []) {
        if (!DRAWABLE.includes(obj.type)) continue;
        const state = stateAt(obj, time);
        if (!state) continue;

        const box = objectBox(obj, state);
        const opacity = Math.min(1, Math.max(0, state.opacity));
        if (opacity <= 0 || state.scale <= 0) continue;

        ctx.save();
        ctx.globalAlpha = opacity;
        ctx.translate(box.left + box.width / 2, box.top + box.height / 2);
        ctx.rotate((state.rotation * Math.PI) / 180);
        ctx.scale(state.scale, state.scale);
        ctx.translate(-box.width / 2, -box.height / 2);

        if (obj.type === 'arrow') {
            drawArrow(ctx, obj, state, box.arrow);
        } else {
            if (obj.type === 'text' || obj.type === 'code') {
                ctx.beginPath();
                ctx.rect(0, 0, box.width, box.height);
                ctx.clip();
            }
            if (obj.type === 'text') drawText(ctx, obj, box.width, box.height, state.local);
            else if (obj.type === 'code') drawCode(ctx, obj, box.width, box.height, state.local);
            else if (obj.type === 'image') drawPicture(ctx, obj, box.width, box.height);
            else drawShape(ctx, obj, box.width, box.height);
        }
        ctx.restore();
    }
}

export const FONT_LOADS = [
    '700 40px Poppins', '500 40px Poppins', 'italic 700 40px Poppins', 'italic 500 40px Poppins',
    '400 40px "JetBrains Mono"', '700 40px "JetBrains Mono"',
];

export const languages = Object.entries(syntax.languages).map(([key, value]) => ({ key, label: value.label }));
export const codeThemes = Object.keys(syntax.themes);
