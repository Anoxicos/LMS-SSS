<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import safeSkyLogo from '@assets/images/logo_safesky blue.webp'

const page = usePage()
const visible = ref(false)
const phase = ref('flight')
const canvas = ref(null)
const progress = ref(0)
const skipButton = ref(null)
let frame = 0
let timers = []
let gl
let started = false

const vertex = `#version 300 es
in vec2 p;
void main(){gl_Position=vec4(p,0.,1.);}`

const fragment = `#version 300 es
precision highp float;
out vec4 color;
uniform vec2 resolution;
uniform float time;
uniform float progress;
float hash(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}
void main(){
  vec2 uv=gl_FragCoord.xy/resolution;
  vec2 p=(gl_FragCoord.xy-.5*resolution)/min(resolution.x,resolution.y);
  float t=time, a=atan(p.y,p.x), r=length(p);
  vec3 c=vec3(.018,.035,.058);
  c+=vec3(.015,.12,.17)*exp(-r*4.2);
  float sweep=progress*1.72-1.18;
  float pathY=.42-.34*progress;
  float head=exp(-length(p-vec2(sweep,pathY))*42.);
  float tail=exp(-abs(p.y-(pathY-.18*(sweep-p.x)))*34.)*smoothstep(sweep-.52,sweep-.06,p.x);
  float ringR=.11+.19*progress;
  float ring=exp(-abs(r-ringR-.012*sin(a*5.-t*1.6))*80.);
  ring*=smoothstep(.56,.92,progress);
  float spokes=pow(max(0.,cos(a*9.+t*.12)),34.)*exp(-abs(r-ringR)*18.)*smoothstep(.67,.95,progress);
  float stars=step(.997,hash(floor(uv*vec2(240.,150.))))*(.3+.7*sin(t*2.+hash(floor(uv*vec2(240.,150.)))*6.));
  c+=vec3(.05,.38,.58)*tail;
  c+=vec3(.18,.67,1.)*ring;
  c+=vec3(.28,.74,1.)*spokes;
  c+=vec3(.42,.84,1.)*head*1.8;
  c+=vec3(.7,.87,1.)*stars;
  float vignette=1.-smoothstep(.2,1.05,length(p));
  color=vec4(c*vignette,1.);
}`

const compile = (context, type, source) => {
  const shader = context.createShader(type)
  if (!shader) return null
  context.shaderSource(shader, source)
  context.compileShader(shader)
  if (!context.getShaderParameter(shader, context.COMPILE_STATUS)) {
    context.deleteShader(shader)
    return null
  }
  return shader
}

const startShader = () => {
  const target = canvas.value
  if (!target) return
  gl = target.getContext('webgl2', { alpha: false, antialias: false, powerPreference: 'low-power' })
  if (!gl) return

  const vs = compile(gl, gl.VERTEX_SHADER, vertex)
  const fs = compile(gl, gl.FRAGMENT_SHADER, fragment)
  if (!vs || !fs) return
  const program = gl.createProgram()
  if (!program) return
  gl.attachShader(program, vs)
  gl.attachShader(program, fs)
  gl.linkProgram(program)
  if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return

  gl.useProgram(program)
  const buffer = gl.createBuffer()
  gl.bindBuffer(gl.ARRAY_BUFFER, buffer)
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW)
  const position = gl.getAttribLocation(program, 'p')
  gl.enableVertexAttribArray(position)
  gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0)
  const uResolution = gl.getUniformLocation(program, 'resolution')
  const uTime = gl.getUniformLocation(program, 'time')
  const uProgress = gl.getUniformLocation(program, 'progress')
  const start = performance.now()
  const render = now => {
    if (!visible.value || !gl) return
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5)
    const width = Math.max(1, Math.round(target.clientWidth * dpr))
    const height = Math.max(1, Math.round(target.clientHeight * dpr))
    if (target.width !== width || target.height !== height) {
      target.width = width
      target.height = height
    }
    progress.value = Math.min(1, (now - start) / 1800)
    gl.viewport(0, 0, width, height)
    gl.uniform2f(uResolution, width, height)
    gl.uniform1f(uTime, (now - start) / 1000)
    gl.uniform1f(uProgress, progress.value)
    gl.drawArrays(gl.TRIANGLES, 0, 3)
    if (progress.value < 1) frame = requestAnimationFrame(render)
  }
  frame = requestAnimationFrame(render)
}

const finish = () => {
  if (!visible.value) return
  phase.value = 'exit'
  const exitDelay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 400
  timers.push(window.setTimeout(() => {
    visible.value = false
  }, exitDelay))
}

const skip = () => finish()
const focusSkip = () => skipButton.value?.focus({ preventScroll: true })

const beginArrival = async () => {
  if (started) return
  const shouldShow = page.props.arrival_intro || new URLSearchParams(window.location.search).get('arrival') === '1'
  if (!shouldShow) return
  started = true
  visible.value = true
  if (new URLSearchParams(window.location.search).has('arrival')) {
    const url = new URL(window.location.href)
    url.searchParams.delete('arrival')
    window.history.replaceState(window.history.state, '', url)
  }
  await nextTick()
  focusSkip()
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    finish()
    return
  }
  startShader()
  timers.push(window.setTimeout(() => { phase.value = 'message' }, 1800))
  timers.push(window.setTimeout(() => { phase.value = 'logo' }, 2700))
  timers.push(window.setTimeout(finish, 3950))
}

onMounted(() => {
  watch(() => [page.props.arrival_intro, page.url], beginArrival, { immediate: true })
})

onBeforeUnmount(() => {
  cancelAnimationFrame(frame)
  timers.forEach(clearTimeout)
  if (gl) gl.getExtension('WEBGL_lose_context')?.loseContext()
})
</script>

<template>
    <section v-if="visible" class="arrival" :class="`is-${phase}`" role="dialog" aria-modal="true" :aria-label="$t('t-arrival-kicker')" @keydown.esc.prevent="skip" @keydown.tab.prevent="focusSkip">
      <canvas ref="canvas" class="arrival-shader" aria-hidden="true"></canvas>
      <div class="arrival-grid" aria-hidden="true"></div>
      <div class="arrival-grain" aria-hidden="true"></div>
      <div v-if="phase === 'flight'" class="arrival-flight" role="status" aria-live="polite">
        <p class="arrival-kicker">{{ $t('t-arrival-kicker') }}</p>
        <div class="arrival-progress"><span :style="{ transform: `scaleX(${progress})` }"></span></div>
        <small>{{ $t('t-arrival-loading') }}</small>
      </div>
      <div v-else-if="phase === 'message'" class="arrival-message" aria-live="polite">
        <p>{{ $t('t-arrival-message') }}</p>
        <span>{{ $t('t-arrival-submessage') }}</span>
      </div>
      <div v-else-if="phase === 'logo'" class="arrival-logo-stage" aria-live="polite">
        <span class="arrival-lens" aria-hidden="true"></span>
        <img :src="safeSkyLogo" :alt="$t('t-arrival-logo-alt')" class="arrival-logo">
        <p class="arrival-signature">{{ $t('t-arrival-signature') }}</p>
      </div>
      <button ref="skipButton" type="button" class="arrival-skip" @click="skip"><span>{{ $t('t-arrival-skip') }}</span><i class="ri-arrow-right-line" aria-hidden="true"></i></button>
    </section>
</template>

<style scoped>
.arrival{--night:#070b14;--jet:#38e1ff;position:fixed;z-index:12000;inset:0;overflow:hidden;display:grid;place-items:center;color:#fff;background:radial-gradient(ellipse at 50% 46%,rgba(12,54,75,.5),transparent 48%),linear-gradient(145deg,#080e18,#05080e 72%)}.arrival.is-exit{background:transparent}.arrival.is-exit>*{visibility:hidden}.arrival.is-exit::before,.arrival.is-exit::after{content:"";position:absolute;z-index:5;left:0;right:0;height:50%;background:linear-gradient(180deg,#070b14 75%,#0d2535);box-shadow:0 0 36px rgba(56,225,255,.16);animation:curtain-open-up .4s cubic-bezier(.76,0,.24,1) both;pointer-events:none;will-change:transform}.arrival.is-exit::before{top:0;transform-origin:top}.arrival.is-exit::after{bottom:0;transform-origin:bottom;animation-name:curtain-open-down}
.arrival-grid{position:absolute;inset:clamp(22px,4vw,54px);border:1px solid rgba(151,202,221,.09);background:linear-gradient(rgba(151,202,221,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(151,202,221,.035) 1px,transparent 1px);background-size:72px 72px;mask-image:radial-gradient(ellipse at center,#000 0,transparent 76%);pointer-events:none}.arrival-grid::before,.arrival-grid::after{content:"";position:absolute;background:rgba(56,225,255,.26)}.arrival-grid::before{left:50%;top:0;bottom:0;width:1px;opacity:.34}.arrival-grid::after{top:50%;left:0;right:0;height:1px;opacity:.34}
.arrival-shader,.arrival-grain{position:absolute;inset:0;width:100%;height:100%}.arrival-shader{display:block}.arrival-grain{pointer-events:none;opacity:.08;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.92' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.38'/%3E%3C/svg%3E")}
.arrival-flight,.arrival-message,.arrival-logo-stage{position:relative;z-index:1;text-align:center}.arrival-kicker,.arrival-signature{color:#c8d1df;font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase}.arrival-kicker{margin:0 0 18px}.arrival-kicker::before{content:"";display:inline-block;width:6px;height:6px;margin:0 9px 1px 0;border-radius:50%;background:var(--jet);box-shadow:0 0 12px var(--jet);animation:beacon 1.4s ease-in-out infinite alternate}.arrival-progress{width:min(220px,52vw);height:3px;margin:auto;overflow:hidden;border-radius:10px;background:rgba(255,255,255,.14)}.arrival-progress span{display:block;width:100%;height:100%;transform-origin:left;border-radius:inherit;background:linear-gradient(90deg,#1f8db9,var(--jet));box-shadow:0 0 14px rgba(56,225,255,.85)}.arrival-flight small{display:block;margin-top:12px;color:#a5b4c5;font-size:10px;letter-spacing:.14em;text-transform:uppercase}
.arrival-message{padding:24px}.arrival-message p{margin:0;color:#f7fbff;font-size:clamp(28px,5.8vw,64px);font-weight:750;letter-spacing:-.055em;line-height:1.02;animation:message-in .56s cubic-bezier(.2,.8,.2,1) both}.arrival-message span{display:block;margin-top:18px;color:#b7c6d8;font-size:clamp(13px,1.6vw,17px);letter-spacing:.04em;animation:message-in .48s .08s cubic-bezier(.2,.8,.2,1) both}
.arrival-logo-stage{width:min(470px,76vw);display:grid;place-items:center;animation:logo-stage-in .35s ease both}.arrival-logo{display:block;width:100%;height:auto;filter:brightness(0) invert(1) drop-shadow(0 0 30px rgba(56,225,255,.24));clip-path:circle(0 at 50%);animation:logo-glass-reveal .95s cubic-bezier(.16,.8,.22,1) .04s forwards}.arrival-lens{position:absolute;z-index:1;width:clamp(78px,14vw,150px);aspect-ratio:1;border:1px solid rgba(199,246,255,.8);border-radius:50%;background:radial-gradient(circle at 35% 30%,rgba(255,255,255,.48),rgba(56,225,255,.09) 45%,transparent 72%);box-shadow:0 0 34px rgba(56,225,255,.28),inset 0 0 26px rgba(255,255,255,.18);backdrop-filter:blur(5px) brightness(1.28);animation:lens-expand .95s cubic-bezier(.16,.8,.22,1) .04s forwards}.arrival-signature{margin:8px 0 0;opacity:0;animation:signature-in .35s ease .82s forwards}
.arrival-skip{position:absolute;right:clamp(20px,4vw,56px);bottom:clamp(18px,4vh,36px);z-index:2;display:flex;align-items:center;gap:9px;padding:11px 14px;border:1px solid rgba(220,237,246,.24);border-radius:3px;color:#e0ebf5;background:rgba(6,14,24,.66);backdrop-filter:blur(10px);font-family:inherit;font-size:10px;font-weight:750;letter-spacing:.13em;text-transform:uppercase;cursor:pointer;transition:color .18s ease,border-color .18s ease,background .18s ease}.arrival-skip i{font-size:14px;color:var(--jet);transition:transform .18s ease}.arrival-skip:hover{color:#fff;border-color:rgba(56,225,255,.7);background:rgba(16,43,58,.82)}.arrival-skip:hover i{transform:translateX(3px)}.arrival-skip:focus-visible{outline:2px solid var(--jet);outline-offset:4px}
@keyframes beacon{to{opacity:.45;box-shadow:0 0 4px var(--jet)}}@keyframes curtain-open-up{to{transform:translateY(-101%)}}@keyframes curtain-open-down{to{transform:translateY(101%)}}@keyframes message-in{from{opacity:0;transform:translateY(14px);filter:blur(8px)}to{opacity:1;transform:none;filter:none}}@keyframes logo-stage-in{from{opacity:0}to{opacity:1}}@keyframes logo-glass-reveal{0%{clip-path:circle(0 at 50%);filter:brightness(2.2) blur(12px)}45%{filter:brightness(1.7) blur(2px)}100%{clip-path:circle(76% at 50%);filter:brightness(0) invert(1) drop-shadow(0 0 30px rgba(56,225,255,.24))}}@keyframes lens-expand{0%{opacity:0;transform:scale(.35)}22%{opacity:1}78%{opacity:.85;transform:scale(3.4)}100%{opacity:0;transform:scale(4.2)}}@keyframes signature-in{to{opacity:1}}
@media(max-width:600px){.arrival-grid{inset:16px;background-size:56px 56px}.arrival-logo-stage{width:88vw}.arrival-message p{max-width:10ch;margin-inline:auto}.arrival-skip{right:18px;bottom:18px}}
@media(prefers-reduced-motion:reduce){.arrival-kicker::before,.arrival-message p,.arrival-message span,.arrival-logo-stage,.arrival-logo,.arrival-lens,.arrival-signature,.arrival.is-exit::before,.arrival.is-exit::after{animation:none!important}.arrival-logo{clip-path:none!important;filter:brightness(0) invert(1)!important}.arrival-lens{display:none}}
</style>
