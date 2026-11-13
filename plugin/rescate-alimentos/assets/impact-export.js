(function (root) {
  'use strict';
  const number = value => new Intl.NumberFormat('es-CO', {maximumFractionDigits:3}).format(value);
  const range = report => report.from.split('-').reverse().join('/') + ' al ' + report.to.split('-').reverse().join('/') + ' · hora de Colombia';
  const estimate = report => report.totals.estimated ?? 'Sin datos para estimar';
  const summary = report => [
    ['Entregas completadas',report.totals.deliveries],
    ['Kilos rescatados',report.totals.kg],
    ['Beneficiarios estimados (raciones)',estimate(report)],
    ['Organizaciones receptoras',report.organizations.length],
    ['Porciones registradas',report.totals.portions],
    ['Piezas registradas',report.totals.units],
    ['Litros registrados',report.totals.liters],
    ['Entregas sin organización registrada',report.without_org]
  ];
  const header = ['Organización receptora','Origen del nombre','Entregas','Kg','Porciones','Piezas','Litros'];
  const orgRows = report => report.organizations.map(org => [org.name,org.registered ? 'Asignada' : 'Registro manual',org.deliveries,org.kg,org.portions,org.units,org.liters]);
  const utf8 = value => new TextEncoder().encode(value);
  const xml = value => String(value).replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f\ufffe\uffff]/g,'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&apos;'})[c]);

  // Stored ZIP entries suffice for the five XML parts of this small workbook.
  const crcTable = Array.from({length:256},(_,i)=>{
    for(let bit=0;bit<8;bit++) i=(i>>>1)^((i&1)?0xedb88320:0);
    return i>>>0;
  });
  function zip(files) {
    const entries=[],directory=[];
    let offset=0,length=0;
    for (const [name,content] of Object.entries(files)) {
      const filename=utf8(name), data=utf8(content);
      let crc=0xffffffff;
      for (const byte of data) crc=(crc>>>8)^crcTable[(crc^byte)&255];
      crc=(crc^0xffffffff)>>>0;
      const local=new Uint8Array(30+filename.length);
      const central=new Uint8Array(46+filename.length);
      const l=new DataView(local.buffer), c=new DataView(central.buffer);
      l.setUint32(0,0x04034b50,true); l.setUint16(4,20,true); l.setUint16(12,33,true);
      l.setUint32(14,crc,true); l.setUint32(18,data.length,true); l.setUint32(22,data.length,true);
      l.setUint16(26,filename.length,true); local.set(filename,30);
      c.setUint32(0,0x02014b50,true); c.setUint16(4,20,true); c.setUint16(6,20,true); c.setUint16(14,33,true);
      c.setUint32(16,crc,true); c.setUint32(20,data.length,true); c.setUint32(24,data.length,true);
      c.setUint16(28,filename.length,true); c.setUint32(42,offset,true); central.set(filename,46);
      entries.push(local,data); directory.push(central); offset+=local.length+data.length; length+=central.length;
    }
    const end=new Uint8Array(22), e=new DataView(end.buffer);
    e.setUint32(0,0x06054b50,true);e.setUint16(8,directory.length,true);e.setUint16(10,directory.length,true);
    e.setUint32(12,length,true);e.setUint32(16,offset,true);
    return new Blob([...entries,...directory,end],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
  }
  function xlsx(report) {
    const rows = [['Reporte de impacto'],['Periodo',range(report)],['Alcance',report.scope],['Método',report.method],[],...summary(report),[],header,...orgRows(report)];
    const ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    const rel = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    const rels = 'http://schemas.openxmlformats.org/package/2006/relationships';
    const worksheet = rows.map((row,i) => '<row r="'+(i+1)+'">'+row.map((value,j)=>{
      const cell=' r="'+String.fromCharCode(65+j)+(i+1)+'"';
      return typeof value==='number' ? '<c'+cell+'><v>'+value+'</v></c>' : '<c'+cell+' t="inlineStr"><is><t xml:space="preserve">'+xml(value)+'</t></is></c>';
    }).join('')+'</row>').join('');
    return zip({
      '[Content_Types].xml':'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
      '_rels/.rels':'<Relationships xmlns="'+rels+'"><Relationship Id="rId1" Type="'+rel+'/officeDocument" Target="xl/workbook.xml"/></Relationships>',
      'xl/workbook.xml':'<workbook xmlns="'+ns+'" xmlns:r="'+rel+'"><sheets><sheet name="Impacto" sheetId="1" r:id="rId1"/></sheets></workbook>',
      'xl/_rels/workbook.xml.rels':'<Relationships xmlns="'+rels+'"><Relationship Id="rId1" Type="'+rel+'/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
      'xl/worksheets/sheet1.xml':'<worksheet xmlns="'+ns+'"><cols><col min="1" max="1" width="41" customWidth="1"/><col min="2" max="2" width="33" customWidth="1"/><col min="3" max="7" width="14" customWidth="1"/></cols><sheetData>'+worksheet+'</sheetData></worksheet>'
    });
  }

  function pdf(report) {
    // Standard PDF fonts use WinAnsi. Encode as hex to keep offsets byte-exact.
    const ansi = {'€':128,'‘':145,'’':146,'“':147,'”':148,'–':150,'—':151,'…':133};
    const hex = value => Array.from(String(value).normalize('NFC'),char=>{
      const cp=char.codePointAt(0);
      return (ansi[char] || (cp>=32 && cp<=255 ? cp : 63)).toString(16).padStart(2,'0');
    }).join('');
    const pages=[];
    let page='',y=0;
    const text = (copy,size,bold,x,at) => 'BT /'+(bold?'B':'R')+' '+size+' Tf 1 0 0 1 '+x+' '+at+' Tm <'+hex(copy)+'> Tj ET\n';
    function next() {
      if (page) pages.push(page);
      page='0.23 0.13 0.10 rg\n'+text('Reporte de impacto',23,true,48,779)+text(range(report),10,false,48,756);
      page+='0.83 0.72 0.66 RG 48 744 m 547 744 l S\n'; y=722;
    }
    function lines(value,size=11,bold=false) {
      const limit=Math.floor(495/(size*.60));
      const words=String(value).split(/\s+/).flatMap(word=>Array.from(word).length>limit ? Array.from(word).join('').match(new RegExp('.{1,'+limit+'}','gu')) : [word]);
      let line='';
      const emit=()=>{if(y<58)next();page+=text(line,size,bold,48,y);y-=size+6;};
      for(const word of words) { if (line && line.length+word.length+1>limit) {emit();line='';} line+=(line?' ':'')+word; }
      if(line)emit();
    }
    next();
    lines(report.scope,10);
    y-=12;
    for (const [label,value] of summary(report)) lines(label+': '+(typeof value==='number'?number(value):value));
    y-=9; lines(report.method,10);
    y-=18; lines('Organizaciones receptoras',15,true);
    if(!report.organizations.length) lines('No hay organizaciones para este periodo.');
    for(const org of report.organizations) {
      if(y<130) next();
      y-=8;
      lines(org.name+' · '+(org.registered?'Asignada':'Registro manual'),11,true);
      lines(number(org.deliveries)+' entregas · '+number(org.kg)+' kg · '+number(org.portions)+' porciones · '+number(org.units)+' piezas · '+number(org.liters)+' litros',10);
    }
    pages.push(page);
    const objects=['<< /Type /Catalog /Pages 2 0 R >>',
      '<< /Type /Pages /Count '+pages.length+' /Kids ['+pages.map((_,i)=>(5+2*i)+' 0 R').join(' ')+'] >>',
      '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
      '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
    pages.forEach((content,i)=>{
      content+=text('Página '+(i+1)+' de '+pages.length,9,false,48,34);
      objects.push('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /R 3 0 R /B 4 0 R >> >> /Contents '+(6+2*i)+' 0 R >>','<< /Length '+content.length+' >>\nstream\n'+content+'endstream');
    });
    let file='%PDF-1.4\n', offsets=['0000000000 65535 f \n'];
    objects.forEach((object,i)=>{offsets.push(String(file.length).padStart(10,'0')+' 00000 n \n');file+=(i+1)+' 0 obj\n'+object+'\nendobj\n';});
    const xref=file.length;
    file+='xref\n0 '+offsets.length+'\n'+offsets.join('')+'trailer\n<< /Size '+offsets.length+' /Root 1 0 R >>\nstartxref\n'+xref+'\n%%EOF\n';
    return new Blob([file],{type:'application/pdf'});
  }

  const api={number,range,estimate,summary,xlsx,pdf};
  if (typeof module === 'object' && module.exports) module.exports=api;
  else root.RAImpact=api;
})(typeof window === 'object' ? window : globalThis);
