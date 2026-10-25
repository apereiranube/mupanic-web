"""Build the original 5x7 point-display digits; no external font source."""
from pathlib import Path
from fontTools.fontBuilder import FontBuilder
from fontTools.pens.ttGlyphPen import TTGlyphPen
PATTERNS = {
 '0':['01110','10001','10011','10101','11001','10001','01110'],
 '1':['00100','01100','00100','00100','00100','00100','01110'],
 '2':['01110','10001','00001','00010','00100','01000','11111'],
 '3':['11110','00001','00001','01110','00001','00001','11110'],
 '4':['00010','00110','01010','10010','11111','00010','00010'],
 '5':['11111','10000','10000','11110','00001','00001','11110'],
 '6':['01110','10000','10000','11110','10001','10001','01110'],
 '7':['11111','00001','00010','00100','01000','01000','01000'],
 '8':['01110','10001','10001','01110','10001','10001','01110'],
 '9':['01110','10001','10001','01111','00001','00001','01110'],
 '.':['00000','00000','00000','00000','00000','00100','00100'],
 '—':['00000','00000','00000','11111','00000','00000','00000'],
}
names = {c:'digit'+c if c.isdigit() else 'period' if c=='.' else 'emdash' for c in PATTERNS}
order = ['.notdef','space']+list(names.values())
glyphs = {'.notdef':TTGlyphPen(None).glyph(),'space':TTGlyphPen(None).glyph()}
for char,rows in PATTERNS.items():
 pen=TTGlyphPen(None)
 for row,bits in enumerate(rows):
  for col,bit in enumerate(bits):
   if bit=='1':
    x=40+col*100;y=60+(6-row)*100
    pen.moveTo((x,y));pen.lineTo((x+82,y));pen.lineTo((x+82,y+82));pen.lineTo((x,y+82));pen.closePath()
 glyphs[names[char]]=pen.glyph()
fb=FontBuilder(1000,isTTF=True)
fb.setupGlyphOrder(order);fb.setupCharacterMap({32:'space',**{ord(c):n for c,n in names.items()}})
fb.setupGlyf(glyphs);fb.setupHorizontalMetrics({n:(580,0) for n in order})
fb.setupHorizontalHeader(ascent=820,descent=-180)
fb.setupNameTable({'familyName':'Fractura Matrix','styleName':'Regular','uniqueFontIdentifier':'FracturaMatrixV11','fullName':'Fractura Matrix','psName':'FracturaMatrix','version':'Version 1.0'})
fb.setupOS2(sTypoAscender=820,sTypoDescender=-180,usWinAscent=820,usWinDescent=180)
fb.setupPost();fb.setupMaxp()
out=Path(__file__).resolve().parents[1]/'overlay/templates/mupanic/fonts/fractura-matrix-v11.ttf'
fb.save(out)
print(f'Created {out.name}: {out.stat().st_size} bytes')
