const fs=require('fs');
const html=fs.readFileSync(process.argv[2],'utf8'),css=fs.readFileSync(process.argv[3],'utf8'),client=fs.readFileSync(process.argv[4],'utf8');
const label=state=>html.match(new RegExp(`<td[^>]*data-shipment-state="${state}"[^>]*aria-label="([^"]+)"`))?.[1]||null;
const labels={unknown:label('unknown'),partial:label('partial'),full:label('full')};
const separateColumn=/<th><span class="fm2-visually-hidden">Отгрузка<\/span><\/th>/.test(html)&&/class="fm2-shipment-cell"/.test(html)&&!/fm2-shipment-copy/.test(html),fixedRowHeight=/\.fm2-control-table td[^}]*block-size:\s*72px/.test(css)&&/\.fm2-shipment-cell[^}]*inline-size:\s*48px/.test(css)&&!/\.fm2-shipment-(?:status|cell)[^}]*background:/.test(css),mobileInlineIcon=/@media\s*\(max-width:\s*680px\)[\s\S]*\.fm2-shipment-cell[^}]*position:\s*absolute/.test(css)&&!/\.fm2-shipment-cell::before/.test(css),usesDelivery4=/delivery-4\.svg/.test(html);
process.stdout.write(JSON.stringify({labels,separateColumn,fixedRowHeight,mobileInlineIcon,usesDelivery4}));
