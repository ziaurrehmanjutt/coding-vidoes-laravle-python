import {
    ANIMATED, FONT_LOADS, HEIGHT, WIDTH, codeThemes, configureAssets, drawFrame, languages, num, objectBox, objectSize, stateAt, trackValue,
} from './engine';

const root = document.getElementById('studio');

if (root) {
    start(root);
}

function start(root) {
    const data = JSON.parse(root.dataset.project);
    const voices = JSON.parse(root.dataset.voices);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    let name = data.name;
    const meta = { type: data.type, category: data.category, language: data.language, tags: data.tags || [] };
    let doc = data.document;
    let selectedId = null;
    let time = 0;
    let playing = false;
    let record = false;
    let history = [];
    let future = [];
    let lastHistory = { key: null, at: 0 };
    let saveTimer = null;
    let dirty = false;
    let inspectorKey = '';

    const $ = (selector) => root.querySelector(selector);
    const canvas = $('#stage');
    const ctx = canvas.getContext('2d');
    const overlay = $('#overlay');
    const inspector = $('#inspector');
    const timeline = $('#timeline');
    const scrub = $('#scrub');
    const clock = $('#clock');
    const saveState = $('#save-state');

    const selected = () => doc.objects.find((obj) => obj.id === selectedId) || null;
    const scale = () => canvas.clientWidth / WIDTH;
    const round = (value, digits = 2) => Math.round(value * 10 ** digits) / 10 ** digits;
    const make = (tag, props = {}, ...children) => {
        const node = Object.assign(document.createElement(tag), props.dom || {});
        for (const [key, value] of Object.entries(props)) {
            if (key === 'dom') continue;
            if (key === 'class') node.className = value;
            else if (key === 'style') node.style.cssText = value;
            else if (key.startsWith('on')) node.addEventListener(key.slice(2), value);
            else node.setAttribute(key, value);
        }
        node.append(...children.flat().filter((child) => child !== null && child !== undefined));
        return node;
    };

    // ------------------------------------------------------------ history and saving

    function snapshot(key) {
        const now = Date.now();
        if (key !== lastHistory.key || now - lastHistory.at > 1000) {
            history.push(JSON.stringify({ name, doc }));
            if (history.length > 100) history.shift();
            future = [];
        }
        lastHistory = { key, at: now };
    }

    function restore(json) {
        const state = JSON.parse(json);
        name = state.name;
        doc = state.doc;
        $('#name').value = name;
        $('#duration').value = doc.duration;
        if (!selected()) selectedId = null;
        changed(true);
    }

    function undo() {
        if (!history.length) return;
        future.push(JSON.stringify({ name, doc }));
        restore(history.pop());
        lastHistory = { key: null, at: 0 };
    }

    function redo() {
        if (!future.length) return;
        history.push(JSON.stringify({ name, doc }));
        restore(future.pop());
    }

    function changed(rebuild = false) {
        dirty = true;
        setSaveState('Unsaved changes');
        clearTimeout(saveTimer);
        saveTimer = setTimeout(save, 800);
        if (rebuild) inspectorKey = '';
        refresh();
    }

    function setSaveState(text, bad = false) {
        saveState.textContent = text;
        saveState.className = `text-xs ${bad ? 'text-red-600' : 'text-slate-500'}`;
    }

    async function save() {
        clearTimeout(saveTimer);
        if (!dirty) return true;
        dirty = false;
        setSaveState('Saving...');
        try {
            const response = await fetch(root.dataset.saveUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ name, ...meta, document: doc }),
            });
            if (!response.ok) throw new Error(String(response.status));
            if (!dirty) setSaveState('All changes saved');
            return true;
        } catch {
            dirty = true;
            setSaveState('Could not save. Retrying...', true);
            saveTimer = setTimeout(save, 4000);
            return false;
        }
    }

    // ------------------------------------------------------------ editing values

    /** The value a property has before enter and exit effects: its keyframes, or its base value. */
    function rawValue(obj, prop) {
        const keys = obj.tracks && obj.tracks[prop];
        const base = num(obj[prop], ANIMATED[prop]);
        return keys && keys.length ? trackValue(keys, localTime(obj), base) : base;
    }

    const localTime = (obj) => Math.max(0, time - num(obj.start, 0));

    function writeKey(obj, prop, value, at = localTime(obj)) {
        obj.tracks ||= {};
        const keys = (obj.tracks[prop] ||= []);
        if (!keys.length && at > 0.04) keys.push({ t: 0, v: num(obj[prop], ANIMATED[prop]), ease: 'ease-in-out' });
        const near = keys.find((key) => Math.abs(key.t - at) < 0.05);
        if (near) near.v = round(value);
        else keys.push({ t: round(at), v: round(value), ease: 'ease-in-out' });
        keys.sort((a, b) => a.t - b.t);
    }

    function setProp(obj, prop, value) {
        if (prop in ANIMATED && (record || (obj.tracks && obj.tracks[prop] && obj.tracks[prop].length))) {
            writeKey(obj, prop, value);
        } else {
            obj[prop] = value;
        }
    }

    function addKeyframeHere(obj) {
        const props = obj.type === 'arrow' ? ['x', 'y', 'x2', 'y2', 'opacity'] : ['x', 'y', 'scale', 'rotation', 'opacity'];
        const at = localTime(obj);
        for (const prop of props) writeKey(obj, prop, rawValue(obj, prop), at);
    }

    // ------------------------------------------------------------ objects

    const NAMES = { image: 'Picture', text: 'Text', code: 'Code', rect: 'Box', ellipse: 'Circle', arrow: 'Arrow', line: 'Line', voice: 'Voice-over' };

    function createObject(kind, overrides = {}) {
        const startAt = round(Math.min(time, doc.duration - 1), 1);
        const base = {
            id: `o${Math.random().toString(36).slice(2, 8)}`,
            type: kind, name: NAMES[kind], start: startAt, duration: Math.max(1, round(doc.duration - startAt, 1)),
            x: 90, y: 760, scale: 1, rotation: 0, opacity: 1, enter: 'none', exit: 'none',
        };
        const extra = {
            text: { text: 'New text', w: 900, h: 220, size: 80, color: '#ffffff', bold: true, align: 'center', enter: 'pop' },
            code: { code: 'print("Hello")', language: 'python', theme: 'vscode', x: 70, w: 940, h: 460, size: 42, cps: 14, typing: true, cursor: true },
            rect: { w: 420, h: 300, x: 330, fill: '#2563eb', strokeWidth: 0, radius: 36 },
            ellipse: { w: 320, h: 320, x: 380, fill: '#ef4444', strokeWidth: 0 },
            arrow: { x: 240, y: 960, x2: 840, y2: 960, stroke: '#facc15', strokeWidth: 12, head: true, draw: 0.8 },
            line: { type: 'arrow', name: 'Line', x: 240, y: 960, x2: 840, y2: 960, stroke: '#ffffff', strokeWidth: 8, head: false, draw: 0.8 },
            voice: { text: 'Say something here.', voice: '', duration: 4 },
            image: {},
        }[kind];
        snapshot('add');
        const obj = { ...base, ...extra, ...overrides };
        doc.objects.push(obj);
        selectedId = obj.id;
        changed(true);
    }

    function duplicate(obj) {
        snapshot('duplicate');
        const copy = JSON.parse(JSON.stringify(obj));
        copy.id = `o${Math.random().toString(36).slice(2, 8)}`;
        copy.name = `${obj.name} copy`;
        for (const prop of ['x', 'y']) copy[prop] = num(copy[prop]) + 40;
        doc.objects.splice(doc.objects.indexOf(obj) + 1, 0, copy);
        selectedId = copy.id;
        changed(true);
    }

    function remove(obj) {
        snapshot('delete');
        doc.objects = doc.objects.filter((item) => item !== obj);
        selectedId = null;
        changed(true);
    }

    function move(obj, step) {
        const index = doc.objects.indexOf(obj);
        const target = index + step;
        if (target < 0 || target >= doc.objects.length) return;
        snapshot('order');
        doc.objects.splice(index, 1);
        doc.objects.splice(target, 0, obj);
        changed(true);
    }

    // ------------------------------------------------------------ preview

    function visibleBox(obj) {
        const state = stateAt(obj, time);
        return state ? { state, box: objectBox(obj, state) } : null;
    }

    function hitTest(x, y) {
        for (const obj of [...doc.objects].reverse()) {
            if (!['text', 'code', 'rect', 'ellipse', 'arrow', 'image'].includes(obj.type)) continue;
            const hit = visibleBox(obj);
            if (!hit) continue;
            const { state, box } = hit;
            if (obj.type === 'arrow') {
                const [ax, ay, bx, by] = [state.x, state.y, state.x2, state.y2];
                const length2 = (bx - ax) ** 2 + (by - ay) ** 2 || 1;
                const t = Math.max(0, Math.min(1, ((x - ax) * (bx - ax) + (y - ay) * (by - ay)) / length2));
                if (Math.hypot(x - (ax + t * (bx - ax)), y - (ay + t * (by - ay))) < Math.max(40, box.arrow.strokeWidth)) return obj;
            } else {
                const [cx, cy] = [box.left + box.width / 2, box.top + box.height / 2];
                if (Math.abs(x - cx) <= (box.width * state.scale) / 2 && Math.abs(y - cy) <= (box.height * state.scale) / 2) return obj;
            }
        }
        return null;
    }

    function drawOverlay() {
        overlay.replaceChildren();
        const obj = selected();
        if (!obj || obj.type === 'voice') return;
        const s = scale();

        const path = moveInfo(obj);
        if (path) {
            const [a, b] = [pathPoint(obj, 0), pathPoint(obj, 1)];
            overlay.append(make('div', {
                class: 'absolute origin-left border-t-2 border-dashed border-amber-400',
                style: `left:${a[0] * s}px;top:${a[1] * s}px;width:${Math.hypot(b[0] - a[0], b[1] - a[1]) * s}px;transform:rotate(${Math.atan2(b[1] - a[1], b[0] - a[0])}rad)`,
            }));
            [['moveStart', a, 'S', 'bg-emerald-500'], ['moveEnd', b, 'E', 'bg-red-500']].forEach(([role, point, letter, color]) => overlay.append(make('div', {
                class: `absolute flex size-7 -translate-x-1/2 -translate-y-1/2 cursor-grab items-center justify-center rounded-full border-2 border-white text-xs font-bold text-white shadow ${color}`,
                style: `left:${point[0] * s}px;top:${point[1] * s}px;touch-action:none;pointer-events:auto`,
                title: role === 'moveStart' ? 'Where it starts. Drag me.' : 'Where it ends. Drag me.',
                'data-handle': role,
            }, letter)));
        }

        const hit = visibleBox(obj);
        if (!hit) return;

        const { state, box } = hit;
        const frame = make('div', {
            class: 'absolute border-2 border-indigo-500',
            style: `left:${(box.left + box.width / 2 - (box.width * state.scale) / 2) * s}px;top:${(box.top + box.height / 2 - (box.height * state.scale) / 2) * s}px;width:${box.width * state.scale * s}px;height:${box.height * state.scale * s}px;transform:rotate(${state.rotation}deg);pointer-events:none`,
        });
        overlay.append(frame);

        const handle = (x, y, role) => overlay.append(make('div', {
            class: 'absolute size-4 -translate-x-1/2 -translate-y-1/2 cursor-pointer rounded-full border-2 border-white bg-indigo-500 shadow',
            style: `left:${x * s}px;top:${y * s}px;touch-action:none;pointer-events:auto`,
            'data-handle': role,
        }));

        if (obj.type === 'arrow') {
            handle(state.x, state.y, 'start');
            handle(state.x2, state.y2, 'end');
        } else {
            handle(box.left + box.width / 2 + (box.width * state.scale) / 2, box.top + box.height / 2 + (box.height * state.scale) / 2, 'resize');
        }
    }

    /** Redraw only what changes while the video plays. */
    function refreshLight() {
        scrub.max = doc.duration;
        scrub.value = time;
        clock.textContent = `${time.toFixed(1)} / ${Number(doc.duration).toFixed(1)} s`;
        drawFrame(ctx, doc, time);
        drawOverlay();
        if (playhead) playhead.style.left = `${150 + time * pixelsPerSecond}px`;
    }

    function refresh() {
        refreshLight();
        drawTimeline();
        syncInspector();
        $('#record').className = `rounded-lg px-3 py-2 text-sm font-semibold ring-1 transition ${record ? 'bg-red-600 text-white ring-red-600' : 'bg-white text-slate-700 ring-slate-300 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700'}`;
    }

    function pointer(event) {
        const rect = canvas.getBoundingClientRect();
        return [(event.clientX - rect.left) / scale(), (event.clientY - rect.top) / scale()];
    }

    function startDrag(event) {
        const [px, py] = pointer(event);
        const role = event.target.dataset && event.target.dataset.handle;
        let obj = selected();

        if (!role) {
            obj = hitTest(px, py);
            selectedId = obj ? obj.id : null;
            inspectorKey = '';
            if (!obj) { refresh(); return; }
        }
        event.preventDefault();
        snapshot(`drag-${obj.id}-${role || 'move'}`);

        const origin = {
            x: rawValue(obj, 'x'), y: rawValue(obj, 'y'), x2: rawValue(obj, 'x2'), y2: rawValue(obj, 'y2'),
            w: num(obj.w, 400), h: num(obj.h, 200),
            center: role === 'moveStart' ? pathPoint(obj, 0) : role === 'moveEnd' ? pathPoint(obj, 1) : null,
            keys: Object.fromEntries(moveProps(obj).map((prop) => [prop, (obj.tracks?.[prop] || []).map((key) => key.v)])),
        };

        const onMove = (moveEvent) => {
            const [mx, my] = pointer(moveEvent);
            const [dx, dy] = [round(mx - px, 1), round(my - py, 1)];
            if (role === 'resize') {
                obj.w = Math.max(40, Math.round(origin.w + dx));
                obj.h = obj.type === 'image' && obj.lock !== false && obj.ratio ? Math.max(20, Math.round(obj.w / obj.ratio)) : Math.max(40, Math.round(origin.h + dy));
            } else if (role === 'moveStart' || role === 'moveEnd') {
                setMovePoint(obj, role === 'moveStart' ? 0 : 1, origin.center[0] + dx, origin.center[1] + dy);
            } else if (!role && moveInfo(obj)) {
                for (const prop of moveProps(obj)) {
                    obj.tracks[prop].forEach((key, index) => { key.v = round(origin.keys[prop][index] + (prop.startsWith('x') ? dx : dy), 1); });
                }
            } else if (role === 'start') {
                setProp(obj, 'x', Math.round(origin.x + dx));
                setProp(obj, 'y', Math.round(origin.y + dy));
            } else if (role === 'end') {
                setProp(obj, 'x2', Math.round(origin.x2 + dx));
                setProp(obj, 'y2', Math.round(origin.y2 + dy));
            } else {
                setProp(obj, 'x', Math.round(origin.x + dx));
                setProp(obj, 'y', Math.round(origin.y + dy));
                if (obj.type === 'arrow') {
                    setProp(obj, 'x2', Math.round(origin.x2 + dx));
                    setProp(obj, 'y2', Math.round(origin.y2 + dy));
                }
            }
            changed();
        };
        const onUp = () => {
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
            changed(true);
        };
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
        refresh();
    }

    // ------------------------------------------------------------ timeline

    let playhead = null;
    let pixelsPerSecond = 1;

    function drawTimeline() {
        const labelWidth = 150;
        const width = Math.max(300, timeline.clientWidth - labelWidth - 16);
        const pps = width / doc.duration;
        pixelsPerSecond = pps;
        timeline.replaceChildren();

        const seek = (event) => {
            const rect = ruler.getBoundingClientRect();
            const move = (e) => {
                time = round(Math.min(doc.duration, Math.max(0, (e.clientX - rect.left) / pps)), 2);
                playing = false;
                refresh();
            };
            move(event);
            const up = () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up); };
            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', up);
        };

        const ticks = [];
        const step = doc.duration > 40 ? 5 : doc.duration > 16 ? 2 : 1;
        for (let t = 0; t <= doc.duration; t += step) {
            ticks.push(make('span', { class: 'absolute top-0 border-l border-slate-300 pl-1 text-[10px] text-slate-500 dark:border-slate-700', style: `left:${t * pps}px;height:100%` }, `${t}s`));
        }
        const ruler = make('div', { class: 'relative h-6 cursor-ew-resize', style: `width:${width}px;touch-action:none`, onpointerdown: seek }, ticks);
        timeline.append(make('div', { class: 'flex' }, make('div', { style: `width:${labelWidth}px`, class: 'shrink-0' }), ruler));

        doc.objects.forEach((obj) => {
            const isSelected = obj.id === selectedId;
            const bar = make('div', {
                class: `absolute top-1 flex h-7 cursor-grab items-center overflow-hidden rounded-md px-2 text-xs font-medium text-white ${barColor(obj.type)} ${isSelected ? 'ring-2 ring-indigo-400' : 'opacity-80'}`,
                style: `left:${num(obj.start) * pps}px;width:${Math.max(12, num(obj.duration, 1) * pps)}px;touch-action:none`,
                onpointerdown: (event) => dragBar(event, obj, pps, 'move'),
            }, make('span', { class: 'pointer-events-none truncate' }, obj.name || obj.type));
            bar.append(
                make('span', { class: 'absolute inset-y-0 left-0 w-2 cursor-ew-resize bg-white/30', onpointerdown: (event) => { event.stopPropagation(); dragBar(event, obj, pps, 'left'); } }),
                make('span', { class: 'absolute inset-y-0 right-0 w-2 cursor-ew-resize bg-white/30', onpointerdown: (event) => { event.stopPropagation(); dragBar(event, obj, pps, 'right'); } }),
            );

            const path = moveInfo(obj);
            if (path) {
                bar.append(make('span', { class: 'pointer-events-none absolute inset-y-0 bg-white/30', style: `left:${path.t0 * pps}px;width:${Math.max(2, (path.t1 - path.t0) * pps)}px` }));
            }

            const keyTimes = new Set();
            for (const keys of Object.values(obj.tracks || {})) keys.forEach((key) => keyTimes.add(round(key.t)));
            const diamonds = [...keyTimes].map((t) => make('button', {
                type: 'button', title: `Keyframe at ${t}s`,
                class: 'absolute top-3 size-3 rotate-45 border border-white bg-amber-400',
                style: `left:${(num(obj.start) + t) * pps - 6}px`,
                onpointerdown: (event) => {
                    event.stopPropagation();
                    selectedId = obj.id;
                    time = round(num(obj.start) + t, 2);
                    playing = false;
                    inspectorKey = '';
                    refresh();
                },
            }));

            const track = make('div', { class: 'relative h-9 rounded bg-slate-100 dark:bg-slate-800', style: `width:${width}px` }, bar, diamonds);
            timeline.append(make('div', {
                class: `flex items-center gap-0 py-0.5 ${isSelected ? 'bg-indigo-50/60 dark:bg-indigo-950/40' : ''}`,
                onclick: () => { if (selectedId !== obj.id) { selectedId = obj.id; inspectorKey = ''; refresh(); } },
            }, make('div', { style: `width:${labelWidth}px`, class: 'shrink-0 truncate pr-2 text-xs text-slate-600 dark:text-slate-300' }, obj.name || obj.type), track));
        });

        playhead = make('div', {
            class: 'pointer-events-none absolute top-0 bottom-0 w-px bg-red-500',
            style: `left:${labelWidth + time * pps}px`,
        });
        timeline.append(playhead);
    }

    const barColor = (type) => ({ image: 'bg-pink-600', text: 'bg-indigo-600', code: 'bg-emerald-600', rect: 'bg-sky-600', ellipse: 'bg-sky-600', arrow: 'bg-amber-600', voice: 'bg-fuchsia-600' }[type] || 'bg-slate-600');

    function dragBar(event, obj, pps, mode) {
        event.preventDefault();
        selectedId = obj.id;
        snapshot(`bar-${obj.id}-${mode}`);
        const startX = event.clientX;
        const origin = { start: num(obj.start), duration: num(obj.duration, 1) };
        const onMove = (e) => {
            const delta = (e.clientX - startX) / pps;
            if (mode === 'move') {
                obj.start = round(Math.max(0, Math.min(doc.duration - 0.1, origin.start + delta)), 1);
            } else if (mode === 'right') {
                obj.duration = round(Math.max(0.1, origin.duration + delta), 1);
            } else {
                const start = Math.max(0, Math.min(origin.start + origin.duration - 0.1, origin.start + delta));
                obj.duration = round(origin.start + origin.duration - start, 1);
                obj.start = round(start, 1);
            }
            changed();
        };
        const onUp = () => {
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
            changed(true);
        };
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
        inspectorKey = '';
        refresh();
    }

    // ------------------------------------------------------------ inspector

    const EASES = [['linear', 'Steady'], ['ease-in-out', 'Smooth'], ['ease-in', 'Slow start'], ['ease-out', 'Slow end'], ['back', 'Overshoot'], ['bounce', 'Bounce']];
    const ENTERS = [['none', 'None'], ['fade', 'Fade in'], ['pop', 'Pop in'], ['slide', 'Slide up']];

    const FIELDS = {
        text: [
            ['text', 'Text', 'textarea'], ['size', 'Size', 'number', { min: 8, max: 400 }], ['color', 'Colour', 'color'],
            ['fill', 'Background', 'color', { optional: true }], ['align', 'Align', 'select', { options: [['left', 'Left'], ['center', 'Centre'], ['right', 'Right']] }],
            ['valign', 'Vertical', 'select', { options: [['top', 'Top'], ['middle', 'Middle']] }],
            ['font', 'Font', 'select', { options: [['sans', 'Poppins'], ['mono', 'Code font']] }], ['bold', 'Bold', 'checkbox'], ['italic', 'Italic', 'checkbox'],
            ['reveal', 'Reveal', 'select', { options: [['none', 'All at once'], ['type', 'Type it out']] }], ['cps', 'Typing speed (letters/s)', 'number', { min: 1, max: 200 }],
            ['radius', 'Corner radius', 'number', { min: 0, max: 500 }],
        ],
        code: [
            ['code', 'Code', 'textarea', { mono: true }], ['language', 'Language', 'select', { options: languages.map((l) => [l.key, l.label]) }],
            ['theme', 'Colours', 'select', { options: codeThemes.map((key) => [key, key]) }], ['size', 'Font size', 'number', { min: 12, max: 120 }],
            ['typing', 'Type it out', 'checkbox'], ['cps', 'Typing speed (letters/s)', 'number', { min: 1, max: 400 }], ['cursor', 'Blinking cursor', 'checkbox'],
        ],
        rect: [['fill', 'Fill', 'color', { optional: true }], ['stroke', 'Outline', 'color'], ['strokeWidth', 'Outline width', 'number', { min: 0, max: 80 }], ['radius', 'Corner radius', 'number', { min: 0, max: 1000 }]],
        ellipse: [['fill', 'Fill', 'color', { optional: true }], ['stroke', 'Outline', 'color'], ['strokeWidth', 'Outline width', 'number', { min: 0, max: 80 }]],
        arrow: [['stroke', 'Colour', 'color'], ['strokeWidth', 'Thickness', 'number', { min: 1, max: 80 }], ['head', 'Arrow head', 'checkbox'], ['draw', 'Draw-on time (s)', 'number', { min: 0, max: 20, step: 0.1 }]],
        image: [['lock', 'Keep proportions', 'checkbox'], ['radius', 'Rounded corners', 'number', { min: 0, max: 2000 }]],
        voice: [['text', 'What to say', 'textarea'], ['voice', 'Voice', 'select', { options: [['', 'Default voice'], ...voices.map((v) => [v.key, v.label])] }]],
    };

    const POSITION = {
        shape: [['x', 'X'], ['y', 'Y'], ['w', 'Width'], ['h', 'Height'], ['scale', 'Scale', { step: 0.05 }], ['rotation', 'Rotate'], ['opacity', 'Opacity', { step: 0.05, min: 0, max: 1 }]],
        arrow: [['x', 'Start X'], ['y', 'Start Y'], ['x2', 'End X'], ['y2', 'End Y'], ['scale', 'Scale', { step: 0.05 }], ['rotation', 'Rotate'], ['opacity', 'Opacity', { step: 0.05, min: 0, max: 1 }]],
    };

    const sectionTitle = (text) => make('h3', { class: 'mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500' }, text);
    const fieldClass = 'field !py-1.5';

    function input(obj, [key, label, kind, options = {}]) {
        const defaults = { cps: obj.type === 'code' ? 20 : 14, radius: obj.type === 'text' ? 24 : 0, size: obj.type === 'code' ? 36 : 64, draw: 0.6, strokeWidth: obj.type === 'arrow' ? 10 : 0 };
        const current = obj[key] ?? defaults[key];
        let control;
        const apply = (value, rebuild = false) => { snapshot(`${obj.id}-${key}`); setProp(obj, key, value); changed(rebuild); };

        if (kind === 'textarea') {
            control = make('textarea', { rows: options.mono ? 7 : 3, class: `${fieldClass} ${options.mono ? 'font-mono' : ''}`, dom: { value: current ?? '' }, oninput: (e) => apply(e.target.value) });
        } else if (kind === 'select') {
            control = make('select', { class: fieldClass, onchange: (e) => apply(e.target.value) },
                options.options.map(([value, text]) => make('option', { value, dom: { selected: (current ?? options.options[0][0]) === value } }, text)));
        } else if (kind === 'checkbox') {
            control = make('input', { type: 'checkbox', class: 'size-4 rounded border-slate-300 text-indigo-600', dom: { checked: ['bold', 'typing', 'cursor', 'head', 'lock'].includes(key) ? current !== false : !!current }, onchange: (e) => apply(e.target.checked) });
        } else if (kind === 'color') {
            control = make('div', { class: 'flex items-center gap-2' },
                make('input', { type: 'color', class: 'h-9 w-14 cursor-pointer rounded border border-slate-300 bg-white', dom: { value: /^#[0-9a-f]{6}$/i.test(current || '') ? current : '#ffffff' }, oninput: (e) => apply(e.target.value) }),
                options.optional ? make('button', { type: 'button', class: 'text-xs text-slate-500 underline', onclick: () => apply(null, true) }, current ? 'Remove' : 'None') : null);
        } else {
            control = make('input', { type: 'number', class: fieldClass, min: options.min ?? '', max: options.max ?? '', step: options.step ?? 1, dom: { value: current ?? '' }, oninput: (e) => { if (e.target.value !== '') apply(num(e.target.value, 0)); } });
        }
        control.dataset.key = key;
        return make('label', { class: kind === 'checkbox' ? 'flex items-center gap-2 text-sm' : 'block text-sm' },
            kind === 'checkbox' ? [control, label] : [make('span', { class: 'mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400' }, label), control]);
    }

    function numberRow(obj, rows) {
        return make('div', { class: 'grid grid-cols-4 gap-2' }, rows.map(([key, label, options = {}]) => input(obj, [key, label, 'number', options])));
    }

    function buildInspector() {
        const obj = selected();
        inspector.replaceChildren();
        if (!obj) {
            inspector.append(
                make('div', { class: 'text-sm text-slate-600 dark:text-slate-400' },
                    make('p', { class: 'font-semibold' }, 'Nothing selected'),
                    make('p', { class: 'mt-1' }, 'Click an object in the preview or the timeline. Add new objects with the buttons on the left.')),
                sectionTitle('Video'),
                make('label', { class: 'mb-3 flex items-center gap-2 text-sm' },
                    make('input', { type: 'checkbox', class: 'size-4 rounded border-slate-300 text-indigo-600', dom: { checked: !!doc.music }, onchange: (e) => { snapshot('music'); doc.music = e.target.checked; changed(); } }),
                    'Background music (quieter while the voice speaks)'),
                make('div', { class: 'grid grid-cols-2 gap-3' },
                    colourInput('Background top', doc.background, (value) => { snapshot('bg'); doc.background = value; changed(); }),
                    colourInput('Background bottom', doc.background2 || doc.background, (value) => { snapshot('bg2'); doc.background2 = value; changed(); })),
            );
            return;
        }

        inspector.append(
            make('div', { class: 'flex items-center justify-between gap-2' },
                make('input', { class: `${fieldClass} font-semibold`, dom: { value: obj.name || '' }, oninput: (e) => { snapshot(`${obj.id}-name`); obj.name = e.target.value; changed(); } }),
                make('div', { class: 'flex shrink-0 gap-1' },
                    smallButton('Up', 'Bring forward', () => move(obj, 1)), smallButton('Down', 'Send backward', () => move(obj, -1)),
                    smallButton('Copy', 'Duplicate', () => duplicate(obj)), smallButton('Delete', 'Delete', () => remove(obj), true))),
        );

        if (obj.type !== 'voice') {
            inspector.append(sectionTitle(record ? 'Position (recording motion)' : 'Position and look'), numberRow(obj, obj.type === 'arrow' ? POSITION.arrow : POSITION.shape));
            inspector.append(sectionTitle('Movement'), movementPanel(obj));
        }
        inspector.append(sectionTitle('Content'), make('div', { class: 'grid gap-3 sm:grid-cols-2' }, (FIELDS[obj.type] || []).map((field) => {
            const node = input(obj, field);
            if (field[2] === 'textarea') node.classList.add('sm:col-span-2');
            return node;
        })));

        inspector.append(sectionTitle('Timing'), make('div', { class: 'grid grid-cols-2 gap-2' },
            input(obj, ['start', 'Appears at (s)', 'number', { min: 0, step: 0.1 }]), input(obj, ['duration', 'Stays for (s)', 'number', { min: 0.1, step: 0.1 }])));

        if (obj.type !== 'voice') {
            inspector.append(make('div', { class: 'mt-2 grid grid-cols-2 gap-2' },
                input(obj, ['enter', 'Comes in', 'select', { options: ENTERS }]), input(obj, ['exit', 'Goes out', 'select', { options: [['none', 'Instantly'], ['fade', 'Fade out']] }])));
            inspector.append(sectionTitle('Other animation'), motionPanel(obj));
        }
    }

    function colourInput(label, value, onInput) {
        return make('label', { class: 'block text-sm' }, make('span', { class: 'mb-1 block text-xs font-medium text-slate-600' }, label),
            make('input', { type: 'color', class: 'h-9 w-full cursor-pointer rounded border border-slate-300', dom: { value: /^#[0-9a-f]{6}$/i.test(value || '') ? value : '#0f172a' }, oninput: (e) => onInput(e.target.value) }));
    }

    function smallButton(text, title, onclick, danger = false) {
        return make('button', { type: 'button', title, onclick, class: `rounded-md px-2 py-1 text-xs font-semibold ring-1 ${danger ? 'text-red-600 ring-red-200 hover:bg-red-50' : 'text-slate-700 ring-slate-300 hover:bg-slate-50 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800'}` }, text);
    }

    // ------------------------------------------------------------ simple movement: start point, end point, times

    const moveProps = (obj) => (obj.type === 'arrow' ? ['x', 'y', 'x2', 'y2'] : ['x', 'y']);

    /** Movement is "simple" when the position has exactly two keyframes (the start and the end). */
    function moveInfo(obj) {
        const tracks = obj.tracks || {};
        const props = moveProps(obj);
        if (!props.every((prop) => tracks[prop] && tracks[prop].length === 2)) return null;
        const [first, second] = tracks.x;
        if (props.some((prop) => tracks[prop][0].t !== first.t || tracks[prop][1].t !== second.t)) return null;
        return { t0: first.t, t1: second.t, ease: first.ease || 'ease-in-out' };
    }

    /** The centre of the object at the start (0) or the end (1) of its movement. */
    function pathPoint(obj, which) {
        if (!moveInfo(obj)) return null;
        const value = (prop) => obj.tracks[prop][which].v;
        if (obj.type === 'arrow') return [(value('x') + value('x2')) / 2, (value('y') + value('y2')) / 2];
        const [width, height] = objectSize(obj);
        return [value('x') + width / 2, value('y') + height / 2];
    }

    function setMovePoint(obj, which, centerX, centerY) {
        const [currentX, currentY] = pathPoint(obj, which);
        const [dx, dy] = [centerX - currentX, centerY - currentY];
        for (const prop of moveProps(obj)) {
            obj.tracks[prop][which].v = round(obj.tracks[prop][which].v + (prop.startsWith('x') ? dx : dy), 1);
        }
    }

    function setMoveTime(obj, which, absolute) {
        const local = round(Math.min(num(obj.duration, 1), Math.max(0, absolute - num(obj.start, 0))), 2);
        for (const prop of moveProps(obj)) {
            const keys = obj.tracks[prop];
            keys[which].t = local;
            if (which === 0 && keys[0].t >= keys[1].t) keys[1].t = round(local + 0.1, 2);
            if (which === 1 && keys[1].t <= keys[0].t) keys[0].t = Math.max(0, round(local - 0.1, 2));
        }
    }

    function enableMovement(obj) {
        const t0 = round(Math.min(localTime(obj), Math.max(0, num(obj.duration, 1) - 0.6)), 1);
        const t1 = round(Math.min(num(obj.duration, 1), t0 + 1.5), 1);
        const shift = rawValue(obj, 'x') > WIDTH * 0.6 ? -260 : 260;
        obj.tracks ||= {};
        for (const prop of moveProps(obj)) {
            const here = rawValue(obj, prop);
            obj.tracks[prop] = [{ t: t0, v: round(here, 1), ease: 'ease-in-out' }, { t: t1, v: round(here + (prop.startsWith('x') ? shift : 0), 1) }];
        }
    }

    function disableMovement(obj) {
        for (const prop of moveProps(obj)) {
            obj[prop] = obj.tracks[prop][0].v;
            delete obj.tracks[prop];
        }
    }

    function movementPanel(obj) {
        const wrapper = make('div', { class: 'space-y-3 text-sm' });
        const info = moveInfo(obj);
        wrapper.append(make('label', { class: 'flex items-center gap-2 font-medium' },
            make('input', {
                type: 'checkbox', class: 'size-4 rounded border-slate-300 text-indigo-600',
                dom: { checked: !!info },
                onchange: (e) => { snapshot('movement'); if (e.target.checked) enableMovement(obj); else if (info) disableMovement(obj); changed(true); },
            }), 'Make this object move'));

        if (!info) {
            wrapper.append(make('p', { class: 'text-xs text-slate-500' }, 'Turn this on to set where the object starts, where it ends, and when it travels.'));
            return wrapper;
        }

        const edit = (action) => (e) => { snapshot(`move-${obj.id}`); action(e); changed(); };
        const field = (label, move, props) => make('label', { class: 'block' },
            make('span', { class: 'mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400' }, label),
            make('input', { type: 'number', step: props.step ?? 1, min: props.min ?? '', class: fieldClass, 'data-move': move, onchange: edit(props.apply) }));
        const here = (which) => smallButton('Use the playhead time', 'Use the time where the playhead is now', () => { snapshot('move-time'); setMoveTime(obj, which, time); changed(); });

        wrapper.append(
            make('div', { class: 'rounded-lg border border-emerald-300 bg-emerald-50/50 p-3 dark:border-emerald-900 dark:bg-emerald-950/30' },
                make('p', { class: 'mb-2 text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-400' }, 'Start (green S)'),
                make('div', { class: 'grid grid-cols-3 gap-2' },
                    field('Left-right', 'ax', { apply: (e) => setMovePoint(obj, 0, num(e.target.value), pathPoint(obj, 0)[1]) }),
                    field('Up-down', 'ay', { apply: (e) => setMovePoint(obj, 0, pathPoint(obj, 0)[0], num(e.target.value)) }),
                    field('Starts moving at (s)', 't0', { step: 0.1, min: 0, apply: (e) => setMoveTime(obj, 0, num(e.target.value)) })),
                make('div', { class: 'mt-2' }, here(0))),
            make('div', { class: 'rounded-lg border border-red-300 bg-red-50/50 p-3 dark:border-red-900 dark:bg-red-950/30' },
                make('p', { class: 'mb-2 text-xs font-semibold uppercase text-red-700 dark:text-red-400' }, 'End (red E)'),
                make('div', { class: 'grid grid-cols-3 gap-2' },
                    field('Left-right', 'bx', { apply: (e) => setMovePoint(obj, 1, num(e.target.value), pathPoint(obj, 1)[1]) }),
                    field('Up-down', 'by', { apply: (e) => setMovePoint(obj, 1, pathPoint(obj, 1)[0], num(e.target.value)) }),
                    field('Arrives at (s)', 't1', { step: 0.1, min: 0, apply: (e) => setMoveTime(obj, 1, num(e.target.value)) })),
                make('div', { class: 'mt-2' }, here(1))),
            make('label', { class: 'block' },
                make('span', { class: 'mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400' }, 'How it travels'),
                make('select', { class: fieldClass, onchange: edit((e) => { for (const prop of moveProps(obj)) obj.tracks[prop][0].ease = e.target.value; }) },
                    EASES.map(([value, text]) => make('option', { value, dom: { selected: info.ease === value } }, text)))),
            make('p', { class: 'text-xs text-slate-500' }, 'Drag the green S and red E dots on the preview. Dragging the object itself slides the whole path. Times are seconds from the start of the video.'),
        );
        return wrapper;
    }

    function motionPanel(obj) {
        const wrapper = make('div', { class: 'space-y-2 text-sm' });
        wrapper.append(make('p', { class: 'text-xs text-slate-500' },
            'Move the playhead, then drag the object (or press the button) to set a keyframe. The object glides between keyframes. Turn on "Record motion" to keyframe every change.'));
        wrapper.append(make('div', { class: 'flex flex-wrap gap-2' },
            smallButton('Add keyframe here', 'Remember the current position, size and opacity at the playhead', () => { snapshot('key'); addKeyframeHere(obj); changed(true); }),
            smallButton('Clear animation', 'Remove all keyframes', () => { snapshot('clear'); obj.tracks = {}; changed(true); }, true)));

        for (const [prop, keys] of Object.entries(obj.tracks || {})) {
            if (!keys.length) continue;
            wrapper.append(make('div', { class: 'rounded-lg border border-slate-200 p-2 dark:border-slate-700' },
                make('p', { class: 'mb-1 text-xs font-semibold uppercase text-slate-500' }, prop),
                keys.map((key, index) => make('div', { class: 'mb-1 flex items-center gap-1' },
                    make('input', { type: 'number', step: 0.1, min: 0, class: `${fieldClass} w-20`, title: 'Seconds after it appears', dom: { value: key.t }, onchange: (e) => { snapshot(`key-${prop}-${index}`); key.t = num(e.target.value, 0, 0, 120); keys.sort((a, b) => a.t - b.t); changed(true); } }),
                    make('input', { type: 'number', step: 1, class: `${fieldClass} w-24`, title: 'Value', dom: { value: key.v }, onchange: (e) => { snapshot(`key-${prop}-${index}`); key.v = num(e.target.value, 0); changed(); } }),
                    make('select', { class: `${fieldClass} flex-1`, title: 'How it gets to the next keyframe', onchange: (e) => { snapshot('ease'); key.ease = e.target.value; changed(); } },
                        EASES.map(([value, text]) => make('option', { value, dom: { selected: (key.ease || 'linear') === value } }, text))),
                    make('button', { type: 'button', title: 'Remove keyframe', class: 'px-2 text-red-600', onclick: () => { snapshot('delkey'); keys.splice(index, 1); if (!keys.length) delete obj.tracks[prop]; changed(true); } }, '×')))));
        }
        return wrapper;
    }

    /** Keep the numbers in step with the playhead without rebuilding the whole panel. */
    function syncInspector() {
        const obj = selected();
        const key = `${selectedId}|${doc.objects.length}|${record}|${JSON.stringify(Object.fromEntries(Object.entries(obj?.tracks || {}).map(([prop, keys]) => [prop, keys.length])))}`;
        if (key !== inspectorKey) {
            inspectorKey = key;
            buildInspector();
            return;
        }
        if (!obj) return;
        const path = moveInfo(obj);
        if (path) {
            const [a, b] = [pathPoint(obj, 0), pathPoint(obj, 1)];
            const values = { ax: a[0], ay: a[1], bx: b[0], by: b[1], t0: num(obj.start) + path.t0, t1: num(obj.start) + path.t1 };
            for (const field of inspector.querySelectorAll('[data-move]')) {
                if (field !== document.activeElement) field.value = round(values[field.dataset.move], 1);
            }
        }
        for (const field of inspector.querySelectorAll('[data-key]')) {
            if (field === document.activeElement) continue;
            const prop = field.dataset.key;
            if (prop in ANIMATED && field.type === 'number') field.value = round(rawValue(obj, prop));
            else if (field.type === 'number' && obj[prop] !== undefined) field.value = obj[prop];
        }
    }

    // ------------------------------------------------------------ playback and rendering

    let lastFrame = 0;
    function tick(now) {
        if (playing) {
            time += (now - lastFrame) / 1000;
            if (time >= doc.duration) time = 0;
            refreshLight();
        }
        lastFrame = now;
        requestAnimationFrame(tick);
    }

    function togglePlay() {
        playing = !playing;
        $('#play').textContent = playing ? 'Pause' : 'Play';
        if (playing && time >= doc.duration - 0.05) time = 0;
    }

    async function render() {
        const button = $('#render');
        const status = $('#render-status');
        button.disabled = true;
        status.textContent = 'Saving...';
        dirty = true;
        if (!(await save())) { status.textContent = 'Could not save, try again.'; button.disabled = false; return; }

        const response = await fetch(root.dataset.renderUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) { status.textContent = body.message || 'Could not start the render.'; button.disabled = false; return; }
        pollRender();
    }

    async function pollRender() {
        const status = $('#render-status');
        try {
            const body = await (await fetch(root.dataset.statusUrl, { headers: { Accept: 'application/json' } })).json();
            if (body.finished) {
                $('#render').disabled = false;
                status.textContent = body.status === 'completed' ? (body.message || 'Video is ready') : `Failed: ${body.message || 'unknown error'}`;
                showResult(body);
                return;
            }
            status.textContent = `${body.label || 'Working'} ${body.progress}% ${body.message ? `- ${body.message}` : ''}`;
            $('#render').disabled = true;
        } catch { /* try again */ }
        setTimeout(pollRender, 1500);
    }

    function showResult(body) {
        const links = $('#render-links');
        links.replaceChildren();
        if (body.playable) {
            links.append(
                make('a', { href: body.file_url, target: '_blank', class: 'font-semibold text-indigo-600 underline' }, 'Watch'),
                make('a', { href: body.download_url, class: 'font-semibold text-indigo-600 underline' }, 'Download MP4'),
            );
        }
    }

    async function uploadImage(event) {
        const file = event.target.files[0];
        event.target.value = '';
        if (!file) return;

        setSaveState('Uploading picture...');
        const form = new FormData();
        form.append('image', file);
        const response = await fetch(root.dataset.assetStoreUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: form });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            setSaveState(body.errors?.image?.[0] || 'The picture could not be uploaded (PNG, JPG, WebP or GIF, up to 8 MB).', true);
            return;
        }

        const width = Math.min(800, body.width);
        createObject('image', { asset: body.asset, name: file.name.replace(/\.[^.]+$/, '').slice(0, 50), ratio: body.width / body.height, w: width, h: Math.round(width * body.height / body.width), x: Math.round((WIDTH - width) / 2), y: 700, enter: 'pop', lock: true });
    }

    // ------------------------------------------------------------ wiring

    async function init() {
        await Promise.all(FONT_LOADS.map((font) => document.fonts.load(font).catch(() => null)));
        canvas.width = WIDTH;
        canvas.height = HEIGHT;

        configureAssets((obj) => (obj.asset ? `${root.dataset.assetUrl}/${obj.asset}` : null), () => refresh());
        $('#image-file').addEventListener('change', uploadImage);
        root.querySelectorAll('[data-add]').forEach((button) => button.addEventListener('click', () => createObject(button.dataset.add)));
        $('#name').addEventListener('input', (e) => { snapshot('name'); name = e.target.value || 'Untitled'; changed(); });
        $('#duration').addEventListener('input', (e) => { snapshot('duration'); doc.duration = num(e.target.value, 12, 1, 120); time = Math.min(time, doc.duration); changed(); });
        const readMeta = () => {
            meta.type = $('#meta-type').value;
            meta.category = $('#meta-category').value || null;
            meta.language = $('#meta-language').value || null;
            meta.tags = $('#meta-tags').value.split(',').map((tag) => tag.trim()).filter(Boolean).slice(0, 12);
            changed();
        };
        ['#meta-type', '#meta-category', '#meta-language', '#meta-tags'].forEach((selector) => $(selector).addEventListener('input', readMeta));
        $('#undo').addEventListener('click', undo);
        $('#redo').addEventListener('click', redo);
        $('#play').addEventListener('click', togglePlay);
        $('#record').addEventListener('click', () => { record = !record; inspectorKey = ''; refresh(); });
        $('#render').addEventListener('click', render);
        scrub.addEventListener('input', () => { time = num(scrub.value); playing = false; $('#play').textContent = 'Play'; refresh(); });
        overlay.addEventListener('pointerdown', startDrag);
        canvas.addEventListener('pointerdown', startDrag);
        window.addEventListener('resize', refresh);
        window.addEventListener('beforeunload', (event) => { if (dirty) { event.preventDefault(); } });

        window.addEventListener('keydown', (event) => {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); event.shiftKey ? redo() : undo(); return; }
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'y') { event.preventDefault(); redo(); return; }
            if (typing) return;
            const obj = selected();
            if (event.key === ' ') { event.preventDefault(); togglePlay(); }
            else if ((event.key === 'Delete' || event.key === 'Backspace') && obj) { event.preventDefault(); remove(obj); }
            else if (obj && event.key.startsWith('Arrow')) {
                event.preventDefault();
                const amount = event.shiftKey ? 10 : 1;
                const [dx, dy] = { ArrowLeft: [-amount, 0], ArrowRight: [amount, 0], ArrowUp: [0, -amount], ArrowDown: [0, amount] }[event.key];
                snapshot(`nudge-${obj.id}`);
                setProp(obj, 'x', rawValue(obj, 'x') + dx);
                setProp(obj, 'y', rawValue(obj, 'y') + dy);
                if (obj.type === 'arrow') { setProp(obj, 'x2', rawValue(obj, 'x2') + dx); setProp(obj, 'y2', rawValue(obj, 'y2') + dy); }
                changed();
            }
        });

        setSaveState('All changes saved');
        if (data.render_status === 'pending' || data.render_status === 'rendering') pollRender();
        else if (data.playable) showResult(data);
        selectedId = doc.objects.find((obj) => obj.id === new URLSearchParams(window.location.search).get('select'))?.id ?? doc.objects[0]?.id ?? null;
        time = Math.min(doc.duration, num(new URLSearchParams(window.location.search).get('t'), 0, 0));
        refresh();
        requestAnimationFrame(tick);
    }

    init();
}
