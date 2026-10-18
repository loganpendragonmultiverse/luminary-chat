// Runtime-only derivatives: preserve the original Meshy files and limb rotations.
export function continuousStep(position, path, budget){
 let x=position.x,z=position.z;
 while(path.length&&budget>0){const t=path[0],d=Math.hypot(t.x-x,t.z-z);if(d<.00001){path.shift();continue}const n=Math.min(budget,d);x+=(t.x-x)/d*n;z+=(t.z-z)/d*n;budget-=n;if(n>=d-.00001)path.shift();else break;}
 return {x:x-position.x,z:z-position.z};
}
export function locomotionClips(THREE,walk,run){
 const derive=clip=>{const c=clip.clone();for(const t of c.tracks){if(!/(?:^|[.\]])Hips\.position$/i.test(t.name)&&t.name!=='Hips.position')continue;const v=t.values;const y0=v[1];for(let i=0;i<v.length;i+=3){v[i]=v[0];v[i+1]=y0+(v[i+1]-y0)*.5;v[i+2]=v[2];}}return c;};
 const walking=derive(walk),running=derive(run);walking.name='world-walking';running.name='world-running';
 const tracks=walking.tracks.map(t=>{const c=t.clone();c.times=new Float32Array([0,.001]);const n=c.getValueSize();c.values=new Float32Array([...t.values.slice(0,n),...t.values.slice(0,n)]);return c;});
 return {walking,running,idle:new THREE.AnimationClip('world-idle',.001,tracks)};
}
