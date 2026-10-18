import * as T from 'three';
// Author additional clips against each existing bind rig. No geometry, weights or original clips are rewritten.
export function prepareFurnitureMotion(c){
 const bones=new Map();c.actor.traverse(o=>{if(o.isBone)bones.set(o.name,o)});
 const need=['Hips','Spine','Spine01','Spine02','LeftUpLeg','RightUpLeg','LeftLeg','RightLeg','LeftFoot','RightFoot','LeftArm','RightArm','LeftForeArm','RightForeArm','LeftHand','RightHand'];
 for(const name of need)if(!bones.has(name))throw Error('Furniture motion requires '+name);
 const snapshot=()=>new Map([...bones].map(([n,b])=>[n,{p:b.position.clone(),q:b.quaternion.clone()}]));
 const apply=s=>{for(const [n,v] of s){bones.get(n).position.copy(v.p);bones.get(n).quaternion.copy(v.q)}c.actor.updateMatrixWorld(true)};
 const neutral=snapshot();
 const point=b=>c.root.worldToLocal(b.getWorldPosition(new T.Vector3()));
 function aim(name,child,dir){
  const b=bones.get(name),end=bones.get(child);c.root.updateMatrixWorld(true);
  const current=end.getWorldPosition(new T.Vector3()).sub(b.getWorldPosition(new T.Vector3())).normalize();
  const target=new T.Vector3(...dir).normalize().transformDirection(c.root.matrixWorld);
  const desired=new T.Quaternion().setFromUnitVectors(current,target).multiply(b.getWorldQuaternion(new T.Quaternion()));
  b.quaternion.copy(b.parent.getWorldQuaternion(new T.Quaternion()).invert().multiply(desired));c.root.updateMatrixWorld(true);
 }
 function hipsAt(y,z=0){const b=bones.get('Hips'),p=point(b);p.y=y;p.z=z;b.position.copy(b.parent.worldToLocal(c.root.localToWorld(p)));c.root.updateMatrixWorld(true)}
 function pose(kind,height){apply(neutral);if(kind==='stand')return snapshot();
  if(kind==='sit'||kind==='lean'){
   hipsAt(kind==='lean'?point(bones.get('Hips')).y-.09:height+.20,kind==='lean'?0:-.08);
   aim('Spine02','Spine01',[0,1,kind==='lean'?.25:.02]);
   for(const side of ['Left','Right']){const sign=side==='Left'?1:-1;
    aim(side+'UpLeg',side+'Leg',[sign*.07,-.40,1]);aim(side+'Leg',side+'Foot',[0,-1,.03]);aim(side+'Foot',side+'ToeBase',[0,-.26,1]);
    aim(side+'Arm',side+'ForeArm',[sign*.06,-1,.20]);aim(side+'ForeArm',side+'Hand',[-sign*.12,-.4,1]);
   }
  }else{
   // Turn the existing hips hierarchy onto its back, then relax arms beside the body.
   for(const side of ['Left','Right']){const sign=side==='Left'?1:-1;aim(side+'Arm',side+'ForeArm',[sign*.15,-1,.03]);aim(side+'ForeArm',side+'Hand',[sign*.04,-1,.05])}
   const b=bones.get('Hips'),axis=new T.Vector3(1,0,0).transformDirection(c.root.matrixWorld);
   const q=new T.Quaternion().setFromAxisAngle(axis,-Math.PI/2).multiply(b.getWorldQuaternion(new T.Quaternion()));
   b.quaternion.copy(b.parent.getWorldQuaternion(new T.Quaternion()).invert().multiply(q));hipsAt(height+.16,-.18);aim('Spine02','Spine01',[0,.12,-1]);aim('Spine01','Spine',[0,.20,-1]);aim('Spine','neck',[0,.26,-1]);aim('neck','Head',[0,.33,-1]);
  }
  return snapshot();
 }
 function clip(name,states,times){const tracks=[];
  for(const [n,b] of bones){tracks.push(new T.QuaternionKeyframeTrack(b.uuid+'.quaternion',times,states.flatMap(s=>s.get(n).q.toArray())));tracks.push(new T.VectorKeyframeTrack(b.uuid+'.position',times,states.flatMap(s=>s.get(n).p.toArray())))}
  const result=new T.AnimationClip(name,times.at(-1),tracks);if(!result.validate())throw Error('Invalid furniture motion');return result;
 }
 c.furnitureMotion={neutral,apply,make(kind,height){const stand=neutral,lean=pose('lean',height),sit=pose('sit',height),lie=kind==='lie'?pose('lie',height):null;apply(neutral);
  return kind==='lie'?{down:clip('Lie down',[stand,lean,sit,lie],[0,.4,1.05,2.6]),up:clip('Get out of bed',[lie,sit,lean,stand],[0,1.3,2,2.6])}:{down:clip('Sit down',[stand,lean,sit],[0,.45,1.8]),up:clip('Stand up',[sit,lean,stand],[0,1.2,1.8])};}};
 apply(neutral);
}
