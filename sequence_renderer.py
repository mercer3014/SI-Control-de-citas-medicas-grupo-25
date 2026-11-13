# -*- coding: utf-8 -*-
"""
Motor de generación de Diagramas de Secuencia UML formal (PUDS / RUP)
Estilo idéntico al estándar Enterprise Architect / Visual Paradigm:
- Marco formal 'sd CU...' con pestaña de corte diagonal superior izquierda.
- Actor stick figure con etiqueta ': Rol' alineado con su línea de vida.
- Cajas de objetos con sombra desplazada (drop shadow) limpia.
- Líneas de vida discontinuas finas y cajas de activación rectangulares.
- Flechas horizontales continuas con punta triangular rellena.
- Texto regular nítido, sin negritas pesadas, sin recuadros ni resaltados de fondo.
- Autollamadas (self-calls) limpias con bucle y punta de flecha dirigida.
- Renderizado con supersampling 3x y filtro Lanczos para calidad nítida sin pixelación.
"""

import os
from PIL import Image, ImageDraw, ImageFont

def get_fonts(scale=3):
    try:
        # Usar fuentes limpias y regulares para que el texto NO se vea remarcado ni resaltado
        font_sd = ImageFont.truetype("C:/Windows/Fonts/segoeui.ttf", int(10.5 * scale))
        font_actor = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", int(11 * scale))
        font_box = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", int(10.5 * scale))
        font_msg = ImageFont.truetype("C:/Windows/Fonts/segoeui.ttf", int(9 * scale))
    except Exception:
        font_sd = ImageFont.truetype("C:/Windows/Fonts/arial.ttf", int(10.5 * scale))
        font_actor = ImageFont.truetype("C:/Windows/Fonts/arialbd.ttf", int(11 * scale))
        font_box = ImageFont.truetype("C:/Windows/Fonts/arialbd.ttf", int(10.5 * scale))
        font_msg = ImageFont.truetype("C:/Windows/Fonts/arial.ttf", int(9 * scale))
    return font_sd, font_actor, font_box, font_msg

def draw_sequence_diagram(filename, title, actor_name, lifelines, activations, messages, width=820, height=540):
    scale = 3
    W = width * scale
    H = height * scale

    # Fondo blanco puro
    img = Image.new("RGBA", (W, H), (255, 255, 255, 255))
    draw = ImageDraw.Draw(img)

    font_sd, font_actor, font_box, font_msg = get_fonts(scale)

    margin = int(16 * scale)
    frame_w = W - 2 * margin
    frame_h = H - 2 * margin

    # 1. Marco exterior
    draw.rectangle(
        [margin, margin, margin + frame_w, margin + frame_h],
        outline=(50, 50, 50),
        width=int(1.2 * scale)
    )

    # 2. Pestaña superior 'sd CU...'
    sd_text = f"sd {title}"
    sd_bbox = draw.textbbox((0, 0), sd_text, font=font_sd)
    tab_tw = sd_bbox[2] - sd_bbox[0] + int(24 * scale)
    tab_th = int(22 * scale)
    tab_cut = int(8 * scale)

    tab_pts = [
        (margin, margin),
        (margin + tab_tw, margin),
        (margin + tab_tw + tab_cut, margin + tab_cut),
        (margin + tab_tw + tab_cut, margin + tab_th),
        (margin, margin + tab_th),
    ]
    draw.polygon(tab_pts, fill=(255, 255, 255), outline=(50, 50, 50), width=int(1.2 * scale))
    draw.text(
        (margin + int(9 * scale), margin + int(3.5 * scale)),
        sd_text,
        fill=(15, 15, 15),
        font=font_sd
    )

    # 3. Posiciones X de las líneas de vida
    num_lifelines = 1 + len(lifelines)  # 0 es el Actor, 1..N son las clases/objetos
    start_x = margin + int(56 * scale)
    avail_w = frame_w - int(90 * scale)
    spacing = avail_w / max(1, (num_lifelines - 1))

    lifeline_x = [start_x + i * spacing for i in range(num_lifelines)]

    top_y = margin + int(32 * scale)
    bot_y = margin + frame_h - int(20 * scale)

    # 4. Dibujar Actor (Índice 0)
    act_cx = lifeline_x[0]
    act_top = top_y + int(6 * scale)
    r_head = int(7 * scale)
    # Cabeza
    draw.ellipse(
        [act_cx - r_head, act_top, act_cx + r_head, act_top + 2 * r_head],
        fill=(255, 255, 255),
        outline=(20, 20, 20),
        width=int(1.3 * scale)
    )
    # Torso
    torso_top = act_top + 2 * r_head
    torso_bot = torso_top + int(14 * scale)
    draw.line([(act_cx, torso_top), (act_cx, torso_bot)], fill=(20, 20, 20), width=int(1.3 * scale))
    # Brazos
    draw.line(
        [(act_cx - int(10 * scale), torso_top + int(6 * scale)), (act_cx + int(10 * scale), torso_top + int(6 * scale))],
        fill=(20, 20, 20),
        width=int(1.3 * scale)
    )
    # Piernas
    draw.line([(act_cx, torso_bot), (act_cx - int(8 * scale), torso_bot + int(13 * scale))], fill=(20, 20, 20), width=int(1.3 * scale))
    draw.line([(act_cx, torso_bot), (act_cx + int(8 * scale), torso_bot + int(13 * scale))], fill=(20, 20, 20), width=int(1.3 * scale))

    # Etiqueta ': Actor'
    lbl_text = f": {actor_name}"
    act_bbox = draw.textbbox((0, 0), lbl_text, font=font_actor)
    act_w = act_bbox[2] - act_bbox[0]
    draw.text((act_cx - act_w / 2, torso_bot + int(16 * scale)), lbl_text, fill=(10, 10, 10), font=font_actor)

    lifeline_start_y = [torso_bot + int(32 * scale)]

    # 5. Dibujar Cajas de Objetos (Índice 1..N) con sombra Enterprise Architect
    box_w = min(int(124 * scale), int(spacing * 0.86))
    box_h = int(30 * scale)
    soff = int(3 * scale)

    for i, name in enumerate(lifelines):
        cx = lifeline_x[i + 1]
        bx = cx - box_w / 2
        by = top_y + int(18 * scale)

        # Sombra plana oscura clásica UML
        draw.rectangle([bx + soff, by + soff, bx + box_w + soff, by + box_h + soff], fill=(185, 185, 185))
        # Caja principal blanca
        draw.rectangle([bx, by, bx + box_w, by + box_h], fill=(255, 255, 255), outline=(20, 20, 20), width=int(1.3 * scale))

        bbox = draw.textbbox((0, 0), name, font=font_box)
        bw = bbox[2] - bbox[0]
        bh = bbox[3] - bbox[1]
        draw.text((cx - bw / 2, by + (box_h - bh) / 2 - int(1 * scale)), name, fill=(10, 10, 10), font=font_box)
        lifeline_start_y.append(by + box_h)

    # 6. Dibujar Líneas Discontinuas (Lifelines)
    def draw_dashed(x, y1, y2):
        curr = y1
        dash = int(5 * scale)
        space = int(4 * scale)
        flag = True
        while curr < y2:
            step = dash if flag else space
            nxt = min(curr + step, y2)
            if flag:
                draw.line([(x, curr), (x, nxt)], fill=(90, 90, 90), width=int(1.1 * scale))
            curr = nxt
            flag = not flag

    for i in range(num_lifelines):
        draw_dashed(lifeline_x[i], lifeline_start_y[i], bot_y)

    # 7. Dibujar Cajas de Activación
    act_w = int(9 * scale)
    for act in activations:
        l_idx = act[0]
        y_s = act[1]
        y_e = act[2]
        is_nested = (len(act) > 3 and act[3])

        cx = lifeline_x[l_idx]
        x_left = cx - act_w / 2 + (int(5 * scale) if is_nested else 0)
        x_right = cx + act_w / 2 + (int(5 * scale) if is_nested else 0)
        y1 = margin + int(y_s * scale)
        y2 = margin + int(y_e * scale)

        draw.rectangle([x_left, y1, x_right, y2], fill=(255, 255, 255), outline=(20, 20, 20), width=int(1.1 * scale))

    # 8. Dibujar Mensajes
    for m in messages:
        f_idx = m["from"]
        t_idx = m["to"]
        my = margin + int(m["y"] * scale)
        lbl = m["label"]
        is_self = m.get("is_self", False)

        if is_self:
            cx = lifeline_x[f_idx]
            loop_w = int(16 * scale)
            loop_h = int(14 * scale)
            x_start = cx + act_w / 2

            # Líneas de la llamada interna
            draw.line([(x_start, my), (x_start + loop_w, my)], fill=(20, 20, 20), width=int(1.2 * scale))
            draw.line([(x_start + loop_w, my), (x_start + loop_w, my + loop_h)], fill=(20, 20, 20), width=int(1.2 * scale))
            draw.line([(x_start + loop_w, my + loop_h), (x_start, my + loop_h)], fill=(20, 20, 20), width=int(1.2 * scale))

            # Punta de flecha hacia la izquierda
            ah = int(5 * scale)
            draw.polygon([
                (x_start, my + loop_h),
                (x_start + ah, my + loop_h - int(3 * scale)),
                (x_start + ah, my + loop_h + int(3 * scale))
            ], fill=(20, 20, 20))

            # Texto regular nítido a la derecha del bucle SIN resaltado ni fondo
            draw.text((x_start + loop_w + int(6 * scale), my + int(1 * scale)), lbl, fill=(15, 15, 15), font=font_msg)
        else:
            x1 = lifeline_x[f_idx]
            x2 = lifeline_x[t_idx]

            if x2 > x1:
                # Flecha hacia la derecha
                sx = x1 + (act_w / 2 if f_idx > 0 else 0)
                ex = x2 - act_w / 2
                draw.line([(sx, my), (ex, my)], fill=(20, 20, 20), width=int(1.2 * scale))
                ah = int(6 * scale)
                draw.polygon([
                    (ex, my),
                    (ex - ah, my - int(3 * scale)),
                    (ex - ah, my + int(3 * scale))
                ], fill=(20, 20, 20))

                # Texto regular posicionado limpiamente SOBRE la flecha SIN recuadro blanco ni fondo resaltado
                tx = sx + int(10 * scale)
                ty = my - int(12 * scale)
                draw.text((tx, ty), lbl, fill=(15, 15, 15), font=font_msg)
            else:
                # Flecha hacia la izquierda
                sx = x1 - (act_w / 2 if f_idx > 0 else 0)
                ex = x2 + act_w / 2
                draw.line([(sx, my), (ex, my)], fill=(20, 20, 20), width=int(1.2 * scale))
                ah = int(6 * scale)
                draw.polygon([
                    (ex, my),
                    (ex + ah, my - int(3 * scale)),
                    (ex + ah, my + int(3 * scale))
                ], fill=(20, 20, 20))

                # Texto regular posicionado limpiamente sobre la flecha
                bbox = draw.textbbox((0, 0), lbl, font=font_msg)
                lw = bbox[2] - bbox[0]
                tx = sx - lw - int(10 * scale)
                ty = my - int(12 * scale)
                draw.text((tx, ty), lbl, fill=(15, 15, 15), font=font_msg)

    # Redimensionar usando Lanczos
    target_w = W // scale
    target_h = H // scale
    final_img = img.resize((target_w, target_h), Image.Resampling.LANCZOS)

    os.makedirs(os.path.dirname(filename), exist_ok=True)
    final_img.save(filename, "PNG", dpi=(300, 300))
    print(f"[OK] Diagrama de secuencia generado: {filename}")
