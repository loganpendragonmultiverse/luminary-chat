// Deterministic idle routine: no network, AI calls, timers or offline worker.
export const ROUTINE_STEP_MS = 90000;
export function routineStep(epoch, now, character) {
  const choices = ['living', 'sit', 'study', 'terrace', 'sit', 'kitchen', 'bedroom', 'lie'];
  const offset = {luminary: 0, mira: 2, aurora: 4}[character] || 0;
  return {index: Math.floor(Math.max(0, now - epoch) / ROUTINE_STEP_MS),
    action: choices[(Math.floor(Math.max(0, now - epoch) / ROUTINE_STEP_MS) + offset) % choices.length]};
}
export const manualLines = {
  luminary: ['You can ask where I want to go, you know. Where are you leading me?', 'Taking the controls? All right—tell me what you’re testing.'],
  mira: ['Oh, now you’re steering me? You had better be taking me somewhere interesting.', 'You could have asked me to come closer instead of commandeering my legs.'],
  aurora: ['I can walk, you know. Where exactly are you leading me?', 'Borrowing my legs now? Fine. Just don’t steer me past a library.'],
};
