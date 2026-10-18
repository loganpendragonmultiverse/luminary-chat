import {solids} from './furniture-layout.mjs';
export const bounds={minX:-7.85,maxX:7.85,minZ:-6.85,maxZ:9.85};
export const rooms={living:{x:0,z:4.5},kitchen:{x:3,z:-3.6},dining:{x:4,z:5.7},study:{x:-4.2,z:1.5},bathroom:{x:-5.5,z:5.9},bedroom:{x:-.05,z:-3.3},terrace:{x:0,z:9.2}};
export const obstacles=solids;
export function passable(x,z,r=.3){return Number.isFinite(x)&&Number.isFinite(z)&&x>=bounds.minX+r&&x<=bounds.maxX-r&&z>=bounds.minZ+r&&z<=bounds.maxZ-r&&!obstacles.some(o=>Math.abs(x-o.x)<o.w/2+r+.01&&Math.abs(z-o.z)<o.d/2+r+.01)}
export function stepMove(p,dx,dz,others=[]){
 const free=(x,z)=>passable(x,z)&&!others.some(o=>Math.hypot(o.x-x,o.z-z)<.55);
 let x=p.x,z=p.z;if(free(x+dx,z+dz))return {x:x+dx,z:z+dz};if(free(x+dx,z))x+=dx;if(free(x,z+dz))z+=dz;return {x,z};
}
const step=.3,cols=Math.floor((bounds.maxX-bounds.minX)/step)+1,rows=Math.floor((bounds.maxZ-bounds.minZ)/step)+1;
const point=i=>({x:bounds.minX+(i%cols)*step,z:bounds.minZ+Math.floor(i/cols)*step});
const index=p=>Math.max(0,Math.min(cols-1,Math.round((p.x-bounds.minX)/step)))+cols*Math.max(0,Math.min(rows-1,Math.round((p.z-bounds.minZ)/step)));
export function route(from,to){
 let end=index(to),start=index(from);if(!passable(point(end).x,point(end).z)){let best=Infinity;for(let i=0;i<cols*rows;i++){const p=point(i),d=Math.hypot(p.x-to.x,p.z-to.z);if(d<best&&passable(p.x,p.z)){best=d;end=i}}}
 const open=new Set([start]),prev=new Map(),cost=new Map([[start,0]]),target=point(end),score=i=>cost.get(i)+Math.hypot(point(i).x-target.x,point(i).z-target.z);
 let found=false;
 while(open.size){let u=[...open].reduce((a,b)=>score(a)<score(b)?a:b);open.delete(u);if(u===end){found=true;break}const p=point(u),cx=u%cols,cz=Math.floor(u/cols);
  for(const [a,b] of [[1,0],[-1,0],[0,1],[0,-1],[1,1],[1,-1],[-1,1],[-1,-1]]){let nx=cx+a,nz=cz+b;if(nx<0||nx>=cols||nz<0||nz>=rows)continue;let v=nx+cols*nz,q=point(v);if(!passable(q.x,q.z))continue;if(a&&b&&(!passable(p.x+a*step,p.z)||!passable(p.x,p.z+b*step)))continue;let c=cost.get(u)+Math.hypot(a,b)*step;if(c<(cost.get(v)??Infinity)){prev.set(v,u);cost.set(v,c);open.add(v)}}
 }
 if(!found)return [];let path=[],n=end;while(n!==start){path.unshift(point(n));n=prev.get(n);if(n===undefined)return []}return path;
}
