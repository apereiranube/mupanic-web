// Genera img/referidos/referidos-preview-vN.jpg (1200x630) para la vista previa del link de invitación.
// Uso: node render.cjs <fondo.png|webp> <salida.jpg>
const fs=require('fs'),path=require('path');const {chromium}=require('playwright');
const T=path.resolve(__dirname,'../../overlay/templates/mupanic');
const [bg,out,modo]=process.argv.slice(2);const chico=modo==='gpt';
const u=p=>'file://'+path.resolve(p);
const html=`<!doctype html><html><head><meta charset="utf-8"><style>
@font-face{font-family:M;font-weight:800;src:url('${u(T+'/fonts/manrope-800.woff2')}')}
@font-face{font-family:M;font-weight:600;src:url('${u(T+'/fonts/manrope-600.woff2')}')}
*{margin:0;box-sizing:border-box}
body{width:1200px;height:630px;overflow:hidden;font-family:M;color:#f3eee4;
background:${chico?"linear-gradient(90deg,#050812e0 0%,#050812b0 22%,#05081240 34%,#05081200 42%)":"linear-gradient(90deg,#050812 0%,#050812f0 26%,#050812a0 42%,#05081200 58%)"},url('${u(bg)}') right center/cover #050812}
${chico?'.c{left:44px!important;width:360px!important}.logo{width:180px!important;margin-bottom:20px!important}.o{font-size:16px!important;letter-spacing:3px!important}h1{font-size:50px!important}p{font-size:21px!important}':''}
.c{position:absolute;left:64px;top:0;bottom:0;width:560px;display:flex;flex-direction:column;justify-content:center}
.logo{width:230px;margin-bottom:26px;filter:drop-shadow(0 4px 18px #000)}
.o{color:#dfb967;font-size:20px;font-weight:800;letter-spacing:4px}
h1{font-size:64px;font-weight:800;line-height:1.02;margin:14px 0 18px;text-shadow:0 3px 20px #000}
h1 em{font-style:normal;color:#f4cd7b}
p{font-size:25px;font-weight:600;color:#d6dbe5;line-height:1.35;text-shadow:0 2px 12px #000}
p b{color:#f8d78d}
.f{position:absolute;inset:0;border:2px solid #e6b65c55;pointer-events:none}
</style></head><body><div class="c">
<img class="logo" src="${u(T+'/img/brand/mu-panic-crystal-bronze-matte-v2.webp')}">
<span class="o">TE INVITARON A JUGAR</span>
<h1>Arrancá con<br><em>3 días de VIP.</em></h1>
<p>Creá tu cuenta con este link.<br><b>Season 6 · Argentina</b></p>
</div><div class="f"></div></body></html>`;
(async()=>{const b=await chromium.launch();const pg=await b.newPage({viewport:{width:1200,height:630}});
const tmp=path.join(require('os').tmpdir(),'ref-preview-'+process.pid+'.html');fs.writeFileSync(tmp,html);await pg.goto('file://'+tmp,{waitUntil:'networkidle'});fs.unlinkSync(tmp);await pg.evaluate(()=>document.fonts.ready);
await pg.screenshot({path:out,type:'jpeg',quality:86});await b.close();console.log('ok',out,fs.statSync(out).size);})();
