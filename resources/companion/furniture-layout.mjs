export const beds=[{id:'bed',name:'Bedroom · bed',x:-4.3,z:-4.9,y:.70,approach:{x:-2.55,z:-4.25},yaw:0,accent:'#8d927f',room:'bedroom'}];
export const seats=[
{id:'sofa-left',name:'Sofa · left seat',x:-1.7,z:.82,y:.46,yaw:0,approach:{x:-1.7,z:1.45},room:'living'},
{id:'sofa-right',name:'Sofa · right seat',x:-.55,z:.82,y:.46,yaw:0,approach:{x:-.55,z:1.45},room:'living'},
{id:'lounge',name:'Lounge chair',x:1.57,z:.9,y:.46,yaw:-Math.PI/2,approach:{x:.8,z:.9},room:'living'},
{id:'study-chair',name:'Study · desk chair',x:-4.8,z:.27,y:.46,yaw:Math.PI,approach:{x:-4.8,z:1.8},room:'study'},
{id:'dining-chair',name:'Dining chair',x:5,z:4.60,y:.48,yaw:Math.PI,approach:{x:5,z:5.95},room:'dining'},
{id:'dining-north',name:'Dining · north chair',x:5,z:2.4,y:.48,yaw:0,approach:{x:5,z:1.05},room:'dining'},
{id:'dining-west',name:'Dining · west chair',x:3.9,z:3.5,y:.48,yaw:Math.PI/2,approach:{x:2.55,z:3.5},room:'dining'},
{id:'dining-east',name:'Dining · east chair',x:6.1,z:3.5,y:.48,yaw:-Math.PI/2,approach:{x:7.45,z:3.5},room:'dining'},
{id:'terrace-chair',name:'Terrace · left chair',x:-3,z:8.73,y:.46,yaw:0,approach:{x:-3,z:9.35},room:'terrace'},
{id:'terrace-right',name:'Terrace · right chair',x:3,z:8.73,y:.46,yaw:0,approach:{x:3,z:9.35},room:'terrace'}];
export const spots=[...seats.map(s=>({...s,kind:'sit'})),...beds.map(s=>({...s,kind:'lie'}))];
export const plants=[[-2.5,6.2],[2.7,-.5],[-6.7,1.5],[7.2,6.1],[-7,-5.9],[7,-6],[-7,8.5],[7,8.5]];
export const walls=[{x:1,z:-4.75,w:.16,d:4.5},{x:-4.3,z:-2.5,w:7.4,d:.16},{x:.95,z:-2.5,w:.1,d:.16},{x:-3,z:-.9,w:.16,d:3.2},{x:-3,z:3.65,w:.16,d:2.3},{x:-3,z:6.7,w:.16,d:.6},{x:-5.5,z:2.5,w:5,d:.16},{x:-5.25,z:7,w:5.5,d:.16},{x:5.25,z:7,w:5.5,d:.16}];
export const solids=[...walls,
...beds.map(b=>({x:b.x,z:b.z,w:2.3,d:2.8})),...beds.map(b=>({x:b.x,z:b.z+1.92,w:1.8,d:.44})),...beds.flatMap(b=>[-1,1].map(side=>({x:b.x+side*1.72,z:b.z-.7,w:.65,d:.6}))),
{x:-1.65,z:-6.55,w:2.2,d:.65},...plants.map(([x,z])=>({x,z,w:.45,d:.45})),
{x:-1.15,z:-1.1,w:3.75,d:.6},{x:-1.15,z:.2,w:3.25,d:1.15},{x:-1.1,z:2.9,w:1.6,d:.8},{x:1.9,z:.9,w:1,d:1},
{x:-4.8,z:-.7,w:3.1,d:1},{x:-4.8,z:.6,w:.65,d:.65},{x:-7.8,z:-.6,w:.5,d:3},
{x:5,z:3.5,w:2.25,d:2.25},...[[5,4.9],[5,2.1],[3.6,3.5],[6.4,3.5]].map(([x,z])=>({x,z,w:.6,d:.6})),
{x:7.4,z:-2.7,w:.8,d:6.6},{x:5.1,z:-5.5,w:2.8,d:.8},{x:5.1,z:-2,w:1.35,d:2.4},{x:7.4,z:1.45,w:.9,d:1.1},
{x:-7.2,z:4.9,w:1.15,d:2.4},{x:-3.9,z:3,w:1.5,d:.65},{x:-3.9,z:6.3,w:.7,d:.8},
{x:-3,z:8.4,w:1,d:1},{x:3,z:8.4,w:1,d:1},{x:0,z:8.4,w:1.2,d:.75}];
