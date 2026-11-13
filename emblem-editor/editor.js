/*
 * |CelMod| Emblem Editor
 * Originally webapp/emblem-editor in the celmod repo; now part of the website.
 * Builds emblem blocks for addons/sourcemod/data/celmod/collectibles.txt.
 */
import * as THREE from './three.module.min.js';

// ---- What the plugin understands -------------------------------------------------------------
const POWERS = [
	['none', 'None (cosmetic)', [], ''],
	['shot', 'Shot: hurts a player', ['value', 'cooldown', 'range', 'dmgtype', 'ignite', 'slow', 'knockback', 'fx'], 'Damage per hit'],
	['flamethrower', 'Flamethrower: short stream of flames', ['value', 'cooldown', 'range', 'ignite', 'sprite'], 'Damage per hit'],
	['spray', 'Spray: harmless effect', ['cooldown', 'range', 'fx'], ''],
	['medic', 'Medic: heals target, right click heals you', ['value', 'cooldown', 'range', 'fx'], 'Health per hit'],
	['magnitude', 'Magnitude: throw and pull', ['value', 'cooldown', 'range', 'fx'], 'Throw force'],
	['portal', 'Portal: two linked portals', ['cooldown', 'range'], ''],
	['chaos', 'Chaos: fiery eruption', ['value', 'duration', 'cooldown', 'range', 'sprite'], 'Radius'],
	['blast', 'Blast: explosion where you aim', ['value', 'cooldown', 'range', 'fx'], 'Magnitude'],
	['demon', 'Demon: burst, right click slams down', ['cooldown', 'range', 'sprite'], ''],
	['blink', 'Blink: teleport where you aim', ['cooldown', 'range'], ''],
	['flash', 'Flash: blinds players facing you', ['cooldown', 'range', 'duration', 'sprite'], ''],
	['smog', 'Smog: smoke cloud, players inside cough', ['value', 'cooldown', 'range', 'duration'], 'Damage per second'],
	['nuke', 'Nuke: headcrab canister from the sky', ['value', 'cooldown', 'range'], 'Impact damage'],
	['speed', 'Speed: run faster', ['value'], 'Speed multiplier'],
	['sprint', 'Sprint: never runs out', [], ''],
	['gravity', 'Gravity: lighter or heavier', ['value'], 'Gravity multiplier'],
	['jump', 'Jump: jump higher', ['value'], 'Jump speed (normal is about 200)'],
	['feather', 'Feather: fall slowly', ['value'], 'Fastest falling speed'],
	['heal', 'Heal: regenerate health', ['value'], 'Health per second'],
	['armor', 'Armor: regenerate suit power', ['value'], 'Suit power per second'],
	['resist', 'Resist: take less damage', ['value'], 'Damage multiplier (0.5 = half)'],
	['cloak', 'Cloak: turn translucent', ['value'], 'Visibility (0 to 255)'],
	['drain', 'Drain: hurts its holder', ['value'], 'Damage per second'],
	['gold', 'Gold: earn money', ['value'], 'CL per minute'],
];
const FX = [
	['beam', 'Beam with impact ring', false], ['flame', 'Flame sprites', true], ['ice', 'Ice shards', true],
	['water', 'Water arc', true], ['energy', 'Energy bolts', true], ['smoke', 'Smoke puffs', true],
	['orb', 'Large orb', true], ['model', 'Flying model (sprite = .mdl)', true], ['spray', 'Sprite spray', true], ['laser', 'Thin laser', false],
	['plasma', 'Plasma beam', false], ['rainbow', 'Rainbow beam', false], ['none', 'Nothing', false],
];
// Skins of the emblem model. Skins without a texture here use stock game textures in game.
// scroll = [texture widths per second, angle in degrees], frames = [count, per second], pulse = seconds per cycle;
// these come from the skins' materials.
// Each skin as its material is set up in game (materials/models/celmod/emblem3/sN.vmt):
//   self: $selfillum, the texture shows at full brightness from every side; phong: [exponent, boost] highlights;
//   rim: $rimlight; bump: normal map; env: reflection strength ($envmap); glass: see-through.
const PHONG = [7, 0.4];
const SKINS = [
	{ c: '#a9c0cb', note: 'glass', self: true, phong: PHONG, rim: true, env: 0.45, glass: 0.55 },
	{ t: 's2', c: '#30343a', note: 'chrome', self: true, env: 1.0 },
	{ c: '#eef3f7', note: 'snow', self: true, phong: PHONG, rim: true },
	{ t: 's4', frames: [66, 20], scroll: [1.0, 0], self: true, phong: PHONG, rim: true },
	{ c: '#ff5a48', note: 'pulsing light', pulse: 0.5, self: true, phong: PHONG, rim: true },
	{ t: 's6', scroll: [0.1, -45], self: true, phong: PHONG, rim: true, bump: 's6_n' },
	{ t: 's7', frames: [121, 30], self: true },
	{ c: '#3fd0ff', note: 'combine shield', self: true, phong: PHONG, rim: true },
	{ t: 's9', scroll: [0.01, 0] },
	{ c: '#cfd6dd', wire: true, note: 'wireframe', self: true },
	{ t: 's11_3', scroll: [0.5, 270], self: true, phong: PHONG, rim: true, env: 0.3 },
	{ c: '#6b6f73', note: 'tram metal', self: true, phong: [10, 0.5], rim: true, bump: 's12_n' },
	{ c: '#2b3440', note: 'citadel metal', self: true, phong: PHONG, rim: true },
	{ t: 's14', scroll: [0.1, -45], self: true, phong: PHONG, rim: true, bump: 's14_n' },
	{ t: 's15', bump: 's15_n' },
	{ c: '#808080', note: 'no preview', self: true }, { c: '#808080', note: 'no preview', self: true },
];
const DEFAULTS = {
	name: 'New Emblem', model: 'models/celmod/phys8.mdl', skin: '0', color: '255 255 255 255', handscale: '1.0',
	handoffset: '0 0 0', handangles: '0 0 0', power: 'none', dropweight: '10',
};
const ORDER = ['name', 'model', 'skin', 'color', 'scale', 'handscale', 'handoffset', 'handangles', 'power', 'value', 'cooldown',
	'duration', 'range', 'dmgtype', 'ignite', 'slow', 'knockback', 'speed', 'fx', 'sprite', 'secondary', 'secondaryvalue', 'secondarycooldown', 'beamcolor', 'jumpboost', 'falldamage', 'playercolor', 'glow', 'upgradesto', 'upgradecost', 'dropweight'];

// ---- State ---------------------------------------------------------------------------------
let emblems = [{ id: 'new_emblem', kv: { ...DEFAULTS } }];
let current = 0;
let showingAll = false;
const $ = (id) => document.getElementById(id);
const form = $('form');

const toHex = (s) => '#' + (s || '255 255 255').trim().split(/\s+/).slice(0, 3).map((n) => Math.max(0, Math.min(255, +n || 0)).toString(16).padStart(2, '0')).join('');
const toRGBA = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(' ') + ' 255';

// ---- KeyValues ------------------------------------------------------------------------------
function parseKV(text) {
	const tokens = [];
	const re = /\/\/[^\n]*|"((?:[^"\\]|\\.)*)"|([{}])|([^\s{}"]+)/g;
	let m;
	while ((m = re.exec(text))) {
		if (m[0].startsWith('//')) continue;
		tokens.push(m[2] ? { brace: m[2] } : { str: m[1] !== undefined ? m[1] : m[3] });
	}
	let i = 0;
	function block() {
		const out = [];
		while (i < tokens.length && tokens[i].brace !== '}') {
			const key = tokens[i++];
			if (!key || key.brace) throw new Error('Expected a key name.');
			const next = tokens[i++];
			if (!next) throw new Error('"' + key.str + '" has no value.');
			if (next.brace === '{') { out.push([key.str, block()]); if (!tokens[i] || tokens[i].brace !== '}') throw new Error('Missing a closing brace.'); i++; }
			else if (next.brace) throw new Error('Unexpected brace after "' + key.str + '".');
			else out.push([key.str, next.str]);
		}
		return out;
	}
	return block();
}
function findEmblemBlocks(tree) {
	for (const [k, v] of tree) {
		if (Array.isArray(v) && k.toLowerCase() === 'emblems') return v;
	}
	for (const [, v] of tree) {
		if (Array.isArray(v)) { const hit = v.some(([, x]) => Array.isArray(x)) ? findEmblemBlocks(v) : null; if (hit) return hit; }
	}
	// Otherwise: a bare list of emblem blocks.
	return tree.every(([, v]) => Array.isArray(v) && v.every(([, x]) => !Array.isArray(x))) ? tree : null;
}
function emblemText(e, indent) {
	const keys = [...ORDER.filter((k) => e.kv[k] !== undefined && e.kv[k] !== ''), ...Object.keys(e.kv).filter((k) => !ORDER.includes(k))];
	const pad = (k) => '\t'.repeat(Math.max(1, 4 - Math.floor((k.length + 2) / 4)));
	return `${indent}"${e.id}"\n${indent}{\n` + keys.map((k) => `${indent}\t"${k}"${pad(k)}"${e.kv[k]}"\n`).join('') + `${indent}}\n`;
}
const allText = () => '\t"Emblems"\n\t{\n' + emblems.map((e) => emblemText(e, '\t\t')).join('') + '\t}\n';

// ---- 3D preview -----------------------------------------------------------------------------
const canvas = $('stage');
const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(35, 4 / 3, 0.1, 50);
const pivot = new THREE.Group(); scene.add(pivot);
// Shaded like the game's VertexLitGeneric materials: most skins are self-lit (the texture at full, even
// brightness from every side), with soft phong highlights, a rim light, normal maps and reflections on top, all
// under even lighting, so each material reads as what it is.
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.NoToneMapping;
const material = new THREE.MeshPhongMaterial({ side: THREE.DoubleSide });
const rimUniforms = { rimColor: { value: new THREE.Color(0, 0, 0) }, rimExponent: { value: 2.0 } };
material.onBeforeCompile = (shader) => {
	shader.uniforms.rimColor = rimUniforms.rimColor;
	shader.uniforms.rimExponent = rimUniforms.rimExponent;
	shader.fragmentShader = 'uniform vec3 rimColor;\nuniform float rimExponent;\n' + shader.fragmentShader.replace(
		'#include <opaque_fragment>',
		'outgoingLight += rimColor * pow(1.0 - clamp(abs(dot(normal, normalize(vViewPosition))), 0.0, 1.0), rimExponent);\n#include <opaque_fragment>');
};
// Even light: soft light from above and below, plus a gentle light from the viewer that makes the highlights.
scene.add(new THREE.HemisphereLight(0xffffff, 0x8a8f96, 1.1));
const headLight = new THREE.DirectionalLight(0xffffff, 1.0);
headLight.position.set(-1, 1.5, 2);
camera.add(headLight);
scene.add(camera);
// A soft studio surrounding, for chrome and glass to reflect.
function makeEnvironment() {
	const cv = document.createElement('canvas'); cv.width = 512; cv.height = 256;
	const ctx = cv.getContext('2d');
	const g = ctx.createLinearGradient(0, 0, 0, 256);
	g.addColorStop(0, '#f4f7fb'); g.addColorStop(0.42, '#b9c2cc'); g.addColorStop(0.5, '#6c7480'); g.addColorStop(0.62, '#3a3f46'); g.addColorStop(1, '#16181b');
	ctx.fillStyle = g; ctx.fillRect(0, 0, 512, 256);
	ctx.fillStyle = 'rgba(255,255,255,0.85)';
	ctx.fillRect(60, 40, 90, 26); ctx.fillRect(300, 30, 140, 20);
	const tex = new THREE.CanvasTexture(cv);
	tex.mapping = THREE.EquirectangularReflectionMapping;
	tex.colorSpace = THREE.SRGBColorSpace;
	return tex;
}
const environment = makeEnvironment();
const animated = {};
let activeSkin = SKINS[0], tintColor = new THREE.Color(1, 1, 1), clock = performance.now();
const loader = new THREE.TextureLoader();
const textures = {};
let mesh = null, yaw = 0.7, pitch = 0.25, dist = 4.2;

fetch('model.json').then((r) => r.json()).then((d) => {
	const g = new THREE.BufferGeometry();
	g.setAttribute('position', new THREE.Float32BufferAttribute(d.positions, 3));
	g.setAttribute('normal', new THREE.Float32BufferAttribute(d.normals, 3));
	g.setAttribute('uv', new THREE.Float32BufferAttribute(d.uvs, 2));
	g.setIndex(d.indices);
	mesh = new THREE.Mesh(g, material);
	pivot.add(mesh);
	applyLook();
}).catch(() => { $('status').textContent = 'The 3D model could not be loaded (model.json).'; });

function wrap(tex, linear) {
	tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
	tex.colorSpace = linear ? THREE.NoColorSpace : THREE.SRGBColorSpace;
	tex.anisotropy = renderer.capabilities.getMaxAnisotropy();
	return tex;
}
function loadTexture(name, linear) {
	if (!textures[name]) textures[name] = wrap(loader.load('textures/' + name + '.png'), linear);
	return textures[name];
}
// Animated skins are stored as a vertical strip of frames and drawn one frame at a time onto a small canvas.
function animatedTexture(name, count) {
	if (animated[name]) return animated[name];
	const cv = document.createElement('canvas'); cv.width = cv.height = 64;
	const a = { canvas: cv, ctx: cv.getContext('2d'), img: new Image(), count, frame: -1, tex: wrap(new THREE.CanvasTexture(cv)) };
	a.img.onload = () => { cv.width = a.img.width; cv.height = a.img.height / count; a.frame = -1; };
	a.img.src = 'textures/' + name + '_frames.png';
	return (animated[name] = a);
}
function applyLook() {
	const e = emblems[current];
	const skin = SKINS[+e.kv.skin] || SKINS[15];
	activeSkin = skin;
	tintColor = new THREE.Color(toHex(e.kv.color));
	material.wireframe = !!skin.wire;

	let map = null;
	if (skin.t) {
		map = skin.frames ? animatedTexture(skin.t, skin.frames[0]).tex : loadTexture(skin.t);
		map.offset.set(0, 0);
	}
	const base = skin.t ? tintColor.clone() : new THREE.Color(skin.c).multiply(tintColor);

	// Self-lit: the texture is the light (emissive), lighting only adds highlights. Otherwise it is lit normally.
	if (skin.self) {
		material.color.set(0x000000);
		material.map = null;
		material.emissive.copy(base);
		material.emissiveMap = map;
	} else {
		material.color.copy(base);
		material.map = map;
		material.emissive.set(0x000000);
		material.emissiveMap = null;
	}
	material.emissiveIntensity = 1;

	// Phong highlights: Source's exponent is for reflected light; three.js uses the half vector, about 4x sharper.
	if (skin.phong) {
		material.specular.setScalar(skin.phong[1]);
		material.shininess = skin.phong[0] * 4;
	} else {
		material.specular.setScalar(skin.self ? 0 : 0.08);
		material.shininess = 12;
	}

	material.normalMap = skin.bump ? loadTexture(skin.bump, true) : null;
	material.normalScale.set(1, 1);

	material.envMap = skin.env ? environment : null;
	material.combine = THREE.AddOperation;
	material.reflectivity = skin.env || 0;

	material.transparent = !!skin.glass;
	material.opacity = skin.glass || 1;
	material.depthWrite = !skin.glass;

	// Rim light: a soft glow along the edges, in a light version of the emblem's colour.
	if (skin.rim) {
		rimUniforms.rimColor.value.copy(base).lerp(new THREE.Color(1, 1, 1), 0.5).multiplyScalar(0.35);
	} else {
		rimUniforms.rimColor.value.setRGB(0, 0, 0);
	}

	material.needsUpdate = true;
}
// Plays the selected skin's animation: frame flipping, scrolling and pulsing.
function animate(now) {
	const dt = Math.min(0.1, (now - clock) / 1000); clock = now;
	const skin = activeSkin, t = now / 1000;
	const tex = material.emissiveMap || material.map;
	if (skin.frames && tex) {
		const a = animated[skin.t], f = Math.floor(t * skin.frames[1]) % skin.frames[0];
		if (a && a.img.complete && a.img.naturalWidth && f !== a.frame) {
			a.frame = f;
			a.ctx.drawImage(a.img, 0, f * a.canvas.height, a.canvas.width, a.canvas.height, 0, 0, a.canvas.width, a.canvas.height);
			a.tex.needsUpdate = true;
		}
	}
	if (skin.scroll && tex) {
		const ang = skin.scroll[1] * Math.PI / 180;
		tex.offset.x = (tex.offset.x + Math.cos(ang) * skin.scroll[0] * dt) % 1;
		tex.offset.y = (tex.offset.y + Math.sin(ang) * skin.scroll[0] * dt) % 1;
	}
	if (skin.pulse) {
		// The light pulses between 10% and full brightness, as its Sine proxy does in game.
		material.emissiveIntensity = 0.1 + 0.9 * (0.5 + 0.5 * Math.sin(t * 2 * Math.PI / skin.pulse));
	}
}
function frame() {
	const w = canvas.clientWidth, h = canvas.clientHeight;
	if (canvas.width !== Math.floor(w * devicePixelRatio) || canvas.height !== Math.floor(h * devicePixelRatio)) {
		renderer.setPixelRatio(devicePixelRatio); renderer.setSize(w, h, false);
		camera.aspect = w / h; camera.updateProjectionMatrix();
	}
	camera.position.set(Math.sin(yaw) * Math.cos(pitch) * dist, Math.sin(pitch) * dist, Math.cos(yaw) * Math.cos(pitch) * dist);
	camera.lookAt(0, 0, 0);
	if (!dragging) yaw += 0.003;
	animate(performance.now());
	renderer.render(scene, camera);
	requestAnimationFrame(frame);
}
let dragging = false, lastX = 0, lastY = 0;
canvas.addEventListener('pointerdown', (ev) => { dragging = true; lastX = ev.clientX; lastY = ev.clientY; canvas.setPointerCapture(ev.pointerId); });
canvas.addEventListener('pointerup', () => { dragging = false; });
canvas.addEventListener('pointermove', (ev) => {
	if (!dragging) return;
	yaw -= (ev.clientX - lastX) * 0.01; pitch = Math.max(-1.4, Math.min(1.4, pitch + (ev.clientY - lastY) * 0.01));
	lastX = ev.clientX; lastY = ev.clientY;
});
canvas.addEventListener('wheel', (ev) => { ev.preventDefault(); dist = Math.max(2, Math.min(10, dist + ev.deltaY * 0.004)); }, { passive: false });
requestAnimationFrame(frame);

// ---- UI -------------------------------------------------------------------------------------
form.power.innerHTML = POWERS.map(([id, label]) => `<option value="${id}">${label}</option>`).join('');
form.fx.innerHTML = FX.map(([id, label]) => `<option value="${id}">${label}</option>`).join('');
$('skins').innerHTML = SKINS.map((s, i) => `<button type="button" data-skin="${i}" title="Skin ${i}${s.note ? ' (' + s.note + ')' : ''}" style="background-color:${s.c || '#555'};${s.t ? `background-image:url(textures/${s.t}.png)` : ''}">${i}</button>`).join('');

function renderList() {
	$('list').innerHTML = emblems.map((e, i) => `<li data-i="${i}" class="${i === current ? 'sel' : ''}"><span class="dot" style="background:${toHex(e.kv.color)}"></span>${escapeHtml(e.kv.name || e.id)}</li>`).join('');
}
const escapeHtml = (s) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

function fillForm() {
	const e = emblems[current], kv = e.kv;
	form.id.value = e.id;
	form.secondary.value = ['fireball', 'icestorm', 'bigshot'].includes(kv.secondary) ? kv.secondary : 'none';
	for (const k of ['name', 'dropweight', 'value', 'cooldown', 'range', 'duration', 'ignite', 'slow', 'knockback', 'speed', 'sprite', 'model', 'handscale', 'scale', 'handoffset', 'handangles', 'secondaryvalue', 'secondarycooldown']) {
		form[k].value = kv[k] ?? '';
	}
	form.color.value = toHex(kv.color);
	form.power.value = POWERS.some(([id]) => id === kv.power) ? kv.power : 'none';
	form.dmgtype.value = kv.dmgtype || 'generic';
	form.fx.value = kv.fx || 'beam';
	form.beamauto.value = kv.beamcolor ? '0' : '1';
	form.beamcolor.value = toHex(kv.beamcolor || kv.color);
	showFields();
	document.querySelectorAll('#skins button').forEach((b) => b.classList.toggle('sel', b.dataset.skin === String(+kv.skin || 0)));
	applyLook(); renderList(); refreshText();
}
function showFields() {
	const power = POWERS.find(([id]) => id === form.power.value) || POWERS[0];
	const fxInfo = FX.find(([id]) => id === form.fx.value) || FX[0];
	const shown = new Set(power[2]);
	if (shown.has('fx')) { shown.add('beamcolor'); if (fxInfo[2]) shown.add('sprite'); }
	if (shown.has('sprite') || power[0] === 'portal') shown.add('beamcolor');
	if (form.secondary.value !== 'none') { shown.add('secondaryvalue'); shown.add('secondarycooldown'); shown.add('sprite'); shown.add('beamcolor'); }
	document.querySelectorAll('label[data-f]').forEach((l) => { l.hidden = !shown.has(l.dataset.f); });
	$('valueLabel').textContent = power[3] || 'Value';
	return shown;
}
function readForm() {
	const e = emblems[current], kv = e.kv;
	const shown = showFields();
	e.id = (form.id.value || 'emblem').toLowerCase().replace(/[^a-z0-9_]/g, '_');
	kv.name = form.name.value; kv.model = form.model.value; kv.color = toRGBA(form.color.value);
	kv.power = form.power.value; kv.dropweight = form.dropweight.value || '0';
	if (form.secondary.value !== 'none') kv.secondary = form.secondary.value; else delete kv.secondary;
	for (const k of ['value', 'cooldown', 'range', 'duration', 'ignite', 'slow', 'knockback', 'sprite', 'secondaryvalue', 'secondarycooldown']) {
		if (shown.has(k) && form[k].value !== '' && !(['ignite', 'slow', 'knockback'].includes(k) && +form[k].value === 0)) kv[k] = form[k].value; else delete kv[k];
	}
	if (shown.has('dmgtype') && form.dmgtype.value !== 'generic') kv.dmgtype = form.dmgtype.value; else delete kv.dmgtype;
	if (shown.has('fx') && form.fx.value !== 'beam') kv.fx = form.fx.value; else delete kv.fx;
	if (shown.has('beamcolor') && form.beamauto.value === '0') kv.beamcolor = toRGBA(form.beamcolor.value); else delete kv.beamcolor;
	if (form.speed.value !== '' && +form.speed.value !== 1 && kv.power !== 'speed') kv.speed = form.speed.value; else delete kv.speed;
	if (form.scale.value !== '' && +form.scale.value !== 1) kv.scale = form.scale.value; else delete kv.scale;
	kv.handscale = form.handscale.value || '1.0'; kv.handoffset = form.handoffset.value || '0 0 0'; kv.handangles = form.handangles.value || '0 0 0';
	applyLook(); renderList(); refreshText();
}
function refreshText() { $('text').value = showingAll ? allText() : emblemText(emblems[current], ''); }
function say(msg) { $('status').textContent = msg; }

form.addEventListener('input', () => { showingAll = false; readForm(); });
$('skins').addEventListener('click', (ev) => {
	const b = ev.target.closest('button'); if (!b) return;
	emblems[current].kv.skin = b.dataset.skin; showingAll = false; fillForm();
});
$('list').addEventListener('click', (ev) => { const li = ev.target.closest('li'); if (!li) return; current = +li.dataset.i; showingAll = false; fillForm(); });
$('add').onclick = () => { emblems.push({ id: uniqueId('new_emblem'), kv: { ...DEFAULTS } }); current = emblems.length - 1; fillForm(); };
$('dup').onclick = () => { const e = emblems[current]; emblems.push({ id: uniqueId(e.id), kv: { ...e.kv } }); current = emblems.length - 1; fillForm(); };
$('del').onclick = () => { if (emblems.length < 2) return say('Keep at least one emblem.'); emblems.splice(current, 1); current = Math.max(0, current - 1); fillForm(); };
function uniqueId(base) { let id = base, n = 2; while (emblems.some((e) => e.id === id)) id = base + '_' + n++; return id; }

$('copy').onclick = async () => {
	showingAll = false; refreshText();
	try { await navigator.clipboard.writeText($('text').value); say('Copied. Paste it inside the "Emblems" section.'); }
	catch { $('text').select(); say('Select the text and copy it.'); }
};
$('showAll').onclick = () => { showingAll = true; refreshText(); say('Showing the whole "Emblems" section.'); };
$('download').onclick = () => {
	const a = document.createElement('a');
	a.href = URL.createObjectURL(new Blob([allText()], { type: 'text/plain' })); a.download = 'emblems.txt'; a.click();
	URL.revokeObjectURL(a.href);
};
function importText(text) {
	try {
		const blocks = findEmblemBlocks(parseKV(text));
		if (!blocks || !blocks.length) return say('No emblems were found in that text.');
		emblems = blocks.filter(([, v]) => Array.isArray(v)).map(([id, rows]) => ({ id: id.toLowerCase(), kv: Object.fromEntries(rows.filter(([, v]) => !Array.isArray(v))) }));
		current = 0; showingAll = false; fillForm(); say('Imported ' + emblems.length + ' emblem' + (emblems.length === 1 ? '' : 's') + '.');
	} catch (err) { say('Could not read that text: ' + err.message); }
}
$('import').onclick = () => importText($('text').value);
$('file').onchange = (ev) => { const f = ev.target.files[0]; if (f) f.text().then(importText); ev.target.value = ''; };

// ---- The server's own emblems ------------------------------------------------------------------
// Served by ../api/emblems.php from the game server's collectibles.txt.
function loadServerEmblems(quiet) {
	return fetch('../api/emblems.php', { cache: 'no-store' })
		.then((r) => { if (!r.ok) throw new Error(r.status); return r.text(); })
		.then((text) => { importText(text); if (!quiet) say('Loaded ' + emblems.length + ' emblems from the server.'); })
		.catch(() => { if (!quiet) say('The server\'s emblem list could not be loaded.'); });
}
$('server').onclick = () => {
	if (emblems.length > 1 && !confirm('Replace the emblems in the editor with the server\'s emblems?')) return;
	loadServerEmblems(false);
};

// ---- Suggestions -------------------------------------------------------------------------------
// The server's ids, to warn before sending: suggestions must be new emblems (suggest.php enforces it).
let serverIds = new Set();
const csrf = document.querySelector('meta[name=csrf]')?.content || '';
function post(body) {
	return fetch('suggest.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify(body) })
		.then((r) => r.json().catch(() => ({ ok: false, error: 'The server sent an unexpected answer.' })));
}
function sayS(msg, ok) { const el = $('suggestStatus'); if (el) { el.textContent = msg; el.className = ok ? 'h' : 'bloody'; } }
if ($('suggest')) {
	$('suggest').onclick = () => {
		readForm();
		const e = emblems[current];
		if (serverIds.has(e.id)) {
			return sayS('"' + e.id + '" is already on the server. Press Duplicate (or change the id) to make it a new emblem first.', false);
		}
		if (!(e.kv.name || '').trim()) return sayS('Give your emblem a name first.', false);
		$('suggest').disabled = true;
		sayS('Sending...', true);
		post({ action: 'submit', kv: emblemText(e, ''), note: ($('note') || {}).value || '' })
			.then((d) => { sayS(d.ok ? d.message : d.error, d.ok); if (d.ok) setTimeout(() => location.reload(), 2500); })
			.finally(() => { $('suggest').disabled = false; });
	};
}
document.querySelectorAll('[data-withdraw]').forEach((b) => {
	b.onclick = () => {
		if (!confirm('Withdraw this suggestion?')) return;
		post({ action: 'withdraw', id: +b.dataset.withdraw }).then((d) => d.ok ? location.reload() : sayS(d.error, false));
	};
});

fillForm();
const preload = document.getElementById('preload');
fetch('../api/emblems.php', { cache: 'no-store' }).then((r) => r.ok ? r.text() : '').then((text) => {
	try { serverIds = new Set(findEmblemBlocks(parseKV(text)).filter(([, v]) => Array.isArray(v)).map(([id]) => id.toLowerCase())); } catch { /* list stays empty; the server still checks */ }
}).catch(() => {}).finally(() => {
	if (preload) {
		importText(JSON.parse(preload.textContent));
		say('Opened a suggested emblem. Changes stay in your browser.');
	} else {
		loadServerEmblems(true).then(() => { if (emblems.length > 1) say('Showing the ' + emblems.length + ' emblems on the server. Pick one to start from, or press New.'); });
	}
});

