import {passable} from './navigation.mjs';
export function nearestInteraction(position,spots,range=1.55){
 return spots.map(spot=>({spot,distance:Math.hypot(position.x-spot.approach.x,position.z-spot.approach.z)}))
 .filter(({spot,distance})=>{if(distance>range)return false;const steps=Math.max(1,Math.ceil(distance/.07));for(let i=0;i<=steps;i++){const t=i/steps;if(!passable(position.x+(spot.approach.x-position.x)*t,position.z+(spot.approach.z-position.z)*t))return false}return true})
 .sort((a,b)=>a.distance-b.distance)[0]?.spot||null;
}
