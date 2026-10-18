// Local scheduling and locomotion arbitration. No network or model calls.
export const profiles = {
  luminary: {delay:22000, resistance:.28, places:['living','sit','kitchen','terrace','study','sit','bedroom','lie']},
  mira: {delay:18000, resistance:.42, places:['sit','bedroom','terrace','living','lie','study','kitchen']},
  aurora: {delay:20000, resistance:.34, places:['study','sit','terrace','living','kitchen','bedroom','lie']},
};
export function localIntent(character,index) {
  const p=profiles[character]||profiles.luminary;
  return p.places[Math.abs(index)%p.places.length];
}
export function mixMovement(manual, autonomous, resistance=0) {
  const hasManual=Math.hypot(manual.x,manual.z)>0;
  const hasAuto=Math.hypot(autonomous.x,autonomous.z)>0;
  if(!hasManual)return {...autonomous};
  if(!hasAuto||resistance<=0)return {...manual};
  const weight=Math.min(.45,Math.max(0,resistance));
  return {x:manual.x*(1-weight)+autonomous.x*weight,z:manual.z*(1-weight)+autonomous.z*weight};
}
export function canIdle(c,now,{active,hidden,busy,pickerOpen}) {
  return c.id===active&&c.ready&&!c.loading&&!hidden&&!busy&&!pickerOpen&&
    now>=(c.nextIdle||0)&&!c.path.length&&!c.pending&&
    !['down','up'].includes(c.resting?.stage)&&now>=(c.autonomyPausedUntil||0);
}
