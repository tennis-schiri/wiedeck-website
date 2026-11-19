#!/usr/bin/env python3
"""Build 23 standalone enamel pin WebPs from the two original HRC image sheets.
The source sheets stay in Git, but only cropped transparent pin assets are deployed.
"""
from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter, ImageFont
import base64, io

SHEET12 = [
 'budapest','hamburg','warsaw','paris',
 'athens','valencia','munich','innsbruck',
 'wroclaw','berlin','porto','stockholm',
]
# Source photo-reference screenshot at 1536x1024. Polygons track the golden edges,
# rather than retaining a rectangular slice of the original paper card.
TRACES = {
 'rome':[(354,151),(440,149),(461,159),(467,181),(462,196),(475,210),(479,247),(474,279),(462,293),(461,299),(438,318),(409,325),(376,321),(348,310),(324,295),(316,277),(318,254),(325,221),(341,194),(349,186)],
 'london-original':[(847,128),(851,138),(854,157),(862,178),(869,184),(899,188),(927,205),(936,230),(938,260),(939,288),(929,309),(910,322),(873,326),(842,318),(809,305),(802,286),(803,246),(812,216),(832,194),(841,183),(839,161)],
 'london-piccadilly':[(1325,150),(1331,144),(1372,145),(1399,152),(1405,167),(1398,201),(1397,274),(1415,290),(1409,305),(1390,318),(1352,324),(1317,319),(1290,304),(1284,289),(1293,271),(1294,168),(1305,154)],
 'dublin':[(366,352),(419,349),(439,359),(450,372),(454,390),(466,413),(474,443),(483,468),(478,487),(466,499),(443,514),(400,516),(364,508),(341,493),(326,475),(326,448),(333,421),(334,391),(343,371)],
 'amsterdam':[(812,371),(821,356),(834,348),(853,359),(864,347),(882,341),(901,352),(912,346),(923,357),(934,370),(944,392),(946,419),(956,442),(953,463),(944,486),(926,503),(902,511),(868,514),(830,502),(805,488),(790,463),(792,436),(803,411)],
 'cologne':[(1311,339),(1319,359),(1327,341),(1334,332),(1345,351),(1355,374),(1365,365),(1376,380),(1385,382),(1392,402),(1407,413),(1419,434),(1425,460),(1425,479),(1411,498),(1390,507),(1357,509),(1310,501),(1270,487),(1261,465),(1263,445),(1277,426),(1287,414),(1296,387)],
 'barcelona':[(370,538),(375,550),(384,535),(391,544),(401,528),(411,544),(420,532),(427,552),(437,542),(443,573),(451,584),(453,612),(458,633),(476,658),(478,681),(467,700),(450,713),(413,717),(378,712),(346,701),(327,682),(324,653),(333,627),(348,610),(358,575)],
 'prague':[(851,543),(859,536),(875,544),(887,534),(902,547),(920,551),(927,573),(936,589),(935,611),(946,641),(951,669),(941,693),(922,710),(901,716),(871,714),(834,708),(804,691),(790,666),(792,636),(809,616),(815,581),(829,563)],
 'vienna':[(1304,549),(1325,541),(1345,545),(1366,544),(1389,551),(1402,561),(1417,583),(1422,613),(1421,647),(1426,669),(1415,691),(1395,705),(1369,715),(1346,714),(1317,703),(1285,693),(1271,672),(1271,646),(1280,614),(1290,580)],
 'lisbon':[(369,735),(391,733),(410,730),(431,736),(446,748),(462,768),(470,794),(474,815),(474,843),(481,860),(476,885),(463,902),(439,913),(404,918),(370,912),(343,899),(320,884),(315,856),(323,828),(332,802),(335,768)],
 'brussels':[(1296,764),(1305,747),(1324,738),(1346,741),(1359,736),(1377,742),(1399,749),(1408,762),(1422,775),(1428,798),(1428,821),(1438,846),(1433,867),(1421,888),(1402,899),(1381,908),(1345,910),(1319,906),(1288,897),(1268,884),(1258,862),(1262,837),(1265,811),(1275,789)],
}

def canvas(im, size=350):
    factor=min((size-18)/im.width,(size-18)/im.height)
    im=im.resize((round(im.width*factor),round(im.height*factor)),Image.Resampling.LANCZOS)
    result=Image.new('RGBA',(size,size),(0,0,0,0))
    result.alpha_composite(im,((size-im.width)//2,(size-im.height)//2))
    return result

def correct_london_original_label(pin):
    """Keep the original Old Park Lane/Big Ben pin motif; replace only its incorrect lower inscription.

    The source collage renders the lower ribbon lettering incorrectly. Work on the
    already scaled, transparent 350px pin to keep the rest of the artwork unchanged.
    """
    ribbon = Image.new('RGBA', pin.size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(ribbon)
    shape = [(78, 282), (110, 292), (175, 301), (240, 292),
             (272, 282), (264, 324), (230, 335), (175, 341),
             (120, 335), (86, 324)]
    draw.polygon(shape, fill=(15, 35, 61, 255))
    draw.line(shape + [shape[0]], fill=(227, 181, 89, 255), width=5, joint='curve')
    draw.line([(99, 297), (175, 308), (251, 297)],
              fill=(248, 220, 152, 200), width=2, joint='curve')
    try:
        font = ImageFont.truetype('DejaVuSerif-Bold.ttf', 27)
    except OSError:
        font = ImageFont.load_default()
    text = 'LONDON'
    left, top, right, bottom = draw.textbbox((0, 0), text, font=font)
    draw.text((175 - (right - left) / 2, 309 - top), text, font=font,
              fill=(255, 235, 174, 255), stroke_width=1, stroke_fill=(69, 38, 17, 255))
    # Never paint outside the already transparent pin silhouette.
    original_alpha = pin.getchannel('A')
    corrected_alpha = Image.new('L', ribbon.size, 0)
    corrected_alpha.paste(ribbon.getchannel('A'))
    from PIL import ImageChops
    ribbon.putalpha(ImageChops.multiply(corrected_alpha, original_alpha))
    return Image.alpha_composite(pin, ribbon)


def main():
    root=Path(__file__).resolve().parent.parent
    store=root/'assets/hrc-art'
    target=root/'public/media/hrc-real-pins'
    target.mkdir(parents=True,exist_ok=True)
    sheet=Image.open(store/'generated12.png').convert('RGBA')
    assert sheet.size==(1448,1086), sheet.size
    w,h=sheet.width//4,sheet.height//3
    for i,slug in enumerate(SHEET12):
        c,r=i%4,i//4
        pin=canvas(sheet.crop((c*w,r*h,(c+1)*w,(r+1)*h)))
        pin.save(target/(slug+'.webp'),'WEBP',quality=90,method=2)
    raw=base64.b64decode(''.join(p.read_text() for p in sorted(store.glob('reference.b64.part*'))))
    poster=Image.open(io.BytesIO(raw)).convert('RGBA')
    assert poster.size==(1536,1024),poster.size
    for slug,poly in TRACES.items():
        # The traced contour used to sit right against the enamel border.
        # Retain the original artwork and a safe 7 px halo around pointed details.
        # Expanding the alpha silhouette (not the rectangular image) avoids
        # chopping off the tips and keeps the original card background transparent.
        margin=12
        x0=max(0,min(x for x,y in poly)-margin)
        y0=max(0,min(y for x,y in poly)-margin)
        x1=min(poster.width,max(x for x,y in poly)+margin)
        y1=min(poster.height,max(y for x,y in poly)+margin)
        im=poster.crop((x0,y0,x1,y1))
        mask=Image.new('L',im.size,0)
        ImageDraw.Draw(mask).polygon([(x-x0,y-y0) for x,y in poly],fill=255)
        mask=mask.filter(ImageFilter.MaxFilter(11))
        mask=mask.filter(ImageFilter.GaussianBlur(.8))
        im.putalpha(mask)
        # Trim only transparent padding; canvas() adds uniform breathing room.
        bounds=im.getbbox()
        if bounds:
            im=im.crop(bounds)
        pin=canvas(im)
        if slug == 'london-original':
            pin=correct_london_original_label(pin)
        pin.save(target/(slug+'.webp'),'WEBP',quality=93,method=2)
    files=list(target.glob('*.webp'))
    assert len(files)==23,('Expected 23 rendered pins',files)
    print('Generated',len(files),'transparent enamel-pin assets')

if __name__=='__main__': main()
