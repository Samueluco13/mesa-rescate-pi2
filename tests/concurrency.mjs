import fs from 'node:fs';
const url=process.argv[2]||'http://127.0.0.1:9400';
const root=new URL('.',import.meta.url);
const fixture=JSON.parse(fs.readFileSync(new URL('.race-auth.json',root),'utf8'));
async function request(user,id,key){
 const response=await fetch(`${url}/?rest_route=/rescate/v1/donations/${id}/reserve`,{method:'POST',redirect:'manual',headers:{'Content-Type':'application/json','X-WP-Nonce':user.nonce,Cookie:user.cookie},body:JSON.stringify({request_key:key})});
 const text=await response.text();let body;try{body=JSON.parse(text)}catch{body={error:text.slice(0,150)}};
 return {actor_id:user.actor_id,http_status:response.status,result_id:body.id??null,holder:body.volunteer_id??null,code:body.code??null,message:body.message??null};
}
const cases=[];
for(let i=0;i<fixture.donations.length;i++){
 const id=fixture.donations[i];const actors=i%2?[...fixture.credentials].reverse():fixture.credentials;
 const attempts=await Promise.all(actors.map(u=>request(u,id,`http_race_${id}_${u.actor_id}_stable`)));
 const winner=attempts.find(a=>a.http_status===200);
 const user=winner&&actors.find(u=>u.actor_id===winner.actor_id);
 const retry=user?await request(user,id,`http_race_${id}_${user.actor_id}_stable`):null;
 const passed=attempts.map(a=>a.http_status).sort().join(',')==='200,409'&&winner.holder===winner.actor_id&&retry?.holder===winner.holder;
 cases.push({donation_id:id,attempts,retry,passed});
 console.log(JSON.stringify({donation:id,statuses:attempts.map(a=>a.http_status),passed}));
}
const report={environment:'WordPress Playground 6.8.11 / PHP 8.3.33 / SQLite / 2 PHP workers, actual concurrent HTTP requests with distinct authenticated cookies',executed_at_utc:new Date().toISOString(),cases,passed:cases.filter(c=>c.passed).length,failed:cases.filter(c=>!c.passed).length,limitations:['InnoDB on the target hosting still requires deployment verification.']};
fs.writeFileSync(new URL('results-race.json',root),JSON.stringify(report,null,2)+'\n');
process.exitCode=report.failed?1:0;
