# -*- coding: utf-8 -*-
"""
Script generador de los 17 Diagramas de Comunicación UML con estereotipos Robustness (BCE)
Fiel al código fuente de Laravel 12 + Vue 3 + Inertia.js (Clínica Vida Salud).
"""

import os
import math
from PIL import Image, ImageDraw, ImageFont

def get_fonts(scale=3):
    try:
        font_tab = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", int(11.5 * scale))
        font_node_title = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", int(10.5 * scale))
        font_node_sub = ImageFont.truetype("C:/Windows/Fonts/segoeuii.ttf", int(9 * scale))
        font_msg = ImageFont.truetype("C:/Windows/Fonts/segoeui.ttf", int(9.2 * scale))
        font_msg_num = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", int(9.2 * scale))
    except Exception:
        font_tab = ImageFont.truetype("C:/Windows/Fonts/arialbd.ttf", int(11.5 * scale))
        font_node_title = ImageFont.truetype("C:/Windows/Fonts/arialbd.ttf", int(10.5 * scale))
        font_node_sub = ImageFont.truetype("C:/Windows/Fonts/ariali.ttf", int(9 * scale))
        font_msg = ImageFont.truetype("C:/Windows/Fonts/arial.ttf", int(9.2 * scale))
        font_msg_num = ImageFont.truetype("C:/Windows/Fonts/arialbd.ttf", int(9.2 * scale))
    return font_tab, font_node_title, font_node_sub, font_msg, font_msg_num

def draw_diagram(output_path, cu_code, cu_title, nodes, messages, width=1060, height=710):
    scale = 3
    W = width * scale
    H = height * scale
    
    img = Image.new("RGBA", (W, H), (255, 255, 255, 255))
    draw = ImageDraw.Draw(img)
    
    font_tab, font_node_title, font_node_sub, font_msg, font_msg_num = get_fonts(scale)
    
    margin = int(18 * scale)
    frame_w = W - 2 * margin
    frame_h = H - 2 * margin
    
    # 1. Marco exterior rectangular
    frame_color = (65, 65, 65, 255)
    draw.rectangle([margin, margin, margin + frame_w, margin + frame_h], outline=frame_color, width=int(1.4 * scale))
    
    # 2. Pestaña superior izquierda (Tab con corte diagonal característico de UML / EA)
    tab_text = f"{cu_code} {cu_title}"
    tab_bbox = draw.textbbox((0, 0), tab_text, font=font_tab)
    text_w = tab_bbox[2] - tab_bbox[0]
    tab_w = text_w + int(22 * scale)
    tab_h = int(24 * scale)
    tab_cut = int(8 * scale)
    
    tab_pts = [
        (margin, margin),
        (margin + tab_w, margin),
        (margin + tab_w + tab_cut, margin + tab_cut),
        (margin + tab_w + tab_cut, margin + tab_h),
        (margin, margin + tab_h)
    ]
    draw.polygon(tab_pts, fill=(255, 255, 255, 255), outline=frame_color, width=int(1.4 * scale))
    draw.text((margin + int(9 * scale), margin + int(4.5 * scale)), tab_text, fill=(20, 20, 20, 255), font=font_tab)
    
    stroke_color = (130, 95, 75, 255)   # Sepia / marrón rosáceo clásico
    stroke_w = int(1.4 * scale)
    
    node_positions = {}
    node_radii = {}
    
    # 3. Dibujar nodos
    for node in nodes:
        nid = node['id']
        ntype = node['type']
        label = node['label']
        cx = int(node['x'] * scale)
        cy = int(node['y'] * scale)
        
        node_positions[nid] = (cx, cy)
        
        if ntype == 'actor':
            r = int(13 * scale)
            node_radii[nid] = r
            
            # Stick figure del actor
            head_top = cy - int(25 * scale)
            head_r = int(9.5 * scale)
            draw.ellipse([cx - head_r, head_top, cx + head_r, head_top + 2 * head_r], 
                         fill=(250, 235, 222, 255), outline=stroke_color, width=stroke_w)
            
            torso_top = head_top + 2 * head_r
            torso_bot = torso_top + int(17 * scale)
            draw.line([(cx, torso_top), (cx, torso_bot)], fill=stroke_color, width=stroke_w)
            
            arm_y = torso_top + int(6.5 * scale)
            arm_span = int(12 * scale)
            draw.line([(cx - arm_span, arm_y), (cx + arm_span, arm_y)], fill=stroke_color, width=stroke_w)
            
            leg_span = int(10 * scale)
            leg_len = int(15 * scale)
            draw.line([(cx, torso_bot), (cx - leg_span, torso_bot + leg_len)], fill=stroke_color, width=stroke_w)
            draw.line([(cx, torso_bot), (cx + leg_span, torso_bot + leg_len)], fill=stroke_color, width=stroke_w)
            
            # Texto debajo
            lbbox = draw.textbbox((0, 0), label, font=font_node_title)
            lw = lbbox[2] - lbbox[0]
            draw.text((cx - lw / 2, torso_bot + leg_len + int(4 * scale)), label, fill=(25, 25, 25, 255), font=font_node_title)
            
            sub_text = "{from Análisis}"
            sbbox = draw.textbbox((0, 0), sub_text, font=font_node_sub)
            sw = sbbox[2] - sbbox[0]
            draw.text((cx - sw / 2, torso_bot + leg_len + int(17 * scale)), sub_text, fill=(85, 85, 85, 255), font=font_node_sub)
            
        else:
            r = int(28 * scale)
            node_radii[nid] = r
            
            # Relleno degradado suave
            grad_surf = Image.new("RGBA", (2 * r + 2, 2 * r + 2), (0, 0, 0, 0))
            gdraw = ImageDraw.Draw(grad_surf)
            steps = 28
            for s in range(steps, 0, -1):
                f = s / float(steps)
                curr_r = int(r * f)
                ox = int(r * (1 - f) * 0.45)
                oy = int(r * (1 - f) * 0.45)
                red = int(255 - (255 - 236) * (1 - f))
                green = int(253 - (253 - 208) * (1 - f))
                blue = int(250 - (250 - 190) * (1 - f))
                gdraw.ellipse([ox + r - curr_r, oy + r - curr_r, ox + r + curr_r, oy + r + curr_r],
                              fill=(red, green, blue, 255))
                
            img.paste(grad_surf, (cx - r, cy - r), grad_surf)
            draw.ellipse([cx - r, cy - r, cx + r, cy + r], outline=stroke_color, width=stroke_w)
            
            # Símbolos BCE
            if ntype == 'boundary':
                gap = int(7 * scale)
                bar_x = cx - r - gap
                bar_h = int(r * 0.75)
                draw.line([(bar_x, cy - bar_h), (bar_x, cy + bar_h)], fill=stroke_color, width=stroke_w)
                draw.line([(bar_x, cy), (cx - r, cy)], fill=stroke_color, width=stroke_w)
                
            elif ntype == 'control':
                arc_r = int(7.5 * scale)
                arc_cx = cx + int(1.5 * scale)
                arc_cy = cy - r
                arc_bbox = [arc_cx - arc_r, arc_cy - arc_r, arc_cx + arc_r, arc_cy + arc_r]
                draw.arc(arc_bbox, start=140, end=350, fill=stroke_color, width=stroke_w)
                arrow_tip_x = arc_cx + int(arc_r * math.cos(math.radians(350)))
                arrow_tip_y = arc_cy + int(arc_r * math.sin(math.radians(350)))
                tip_size = int(3.5 * scale)
                draw.line([(arrow_tip_x, arrow_tip_y), (arrow_tip_x - tip_size, arrow_tip_y - int(tip_size * 0.8))], fill=stroke_color, width=stroke_w)
                draw.line([(arrow_tip_x, arrow_tip_y), (arrow_tip_x - int(tip_size * 0.3), arrow_tip_y + int(tip_size * 1.1))], fill=stroke_color, width=stroke_w)
                
            elif ntype == 'entity':
                line_y = cy + r + int(2.5 * scale)
                half_w = int(r * 0.88)
                draw.line([(cx - half_w, line_y), (cx + half_w, line_y)], fill=stroke_color, width=stroke_w)
                
            # Etiqueta
            lbbox = draw.textbbox((0, 0), label, font=font_node_title)
            lw = lbbox[2] - lbbox[0]
            label_y = cy + r + (int(7 * scale) if ntype != 'entity' else int(9.5 * scale))
            draw.text((cx - lw / 2, label_y), label, fill=(25, 25, 25, 255), font=font_node_title)
            
    # 4. Dibujar mensajes y flechas de comunicación
    link_color = (95, 110, 125, 255)
    link_w = int(1.2 * scale)
    
    for msg in messages:
        u_id = msg['from']
        v_id = msg['to']
        text = msg['text']
        
        ux, uy = node_positions[u_id]
        vx, vy = node_positions[v_id]
        
        ru = node_radii[u_id] + int(2 * scale)
        rv = node_radii[v_id] + int(2 * scale)
        
        dx = vx - ux
        dy = vy - uy
        dist = math.hypot(dx, dy)
        if dist == 0:
            continue
            
        unit_dx = dx / dist
        unit_dy = dy / dist
        perp_dx = -unit_dy
        perp_dy = unit_dx
        
        # Desplazamiento lateral de la línea para líneas bidireccionales / paralelas
        side_off = int(msg.get('side_off', 0) * scale)
        
        start_x = ux + unit_dx * ru + perp_dx * side_off
        start_y = uy + unit_dy * ru + perp_dy * side_off
        end_x = vx - unit_dx * rv + perp_dx * side_off
        end_y = vy - unit_dy * rv + perp_dy * side_off
        
        # Dibujar línea
        draw.line([(start_x, start_y), (end_x, end_y)], fill=link_color, width=link_w)
        
        # Punta de flecha en end_x, end_y
        arrow_len = int(8.5 * scale)
        arrow_angle = math.radians(24)
        angle = math.atan2(end_y - start_y, end_x - start_x)
        
        p1_x = end_x - arrow_len * math.cos(angle - arrow_angle)
        p1_y = end_y - arrow_len * math.sin(angle - arrow_angle)
        p2_x = end_x - arrow_len * math.cos(angle + arrow_angle)
        p2_y = end_y - arrow_len * math.sin(angle + arrow_angle)
        
        draw.line([(end_x, end_y), (p1_x, p1_y)], fill=link_color, width=link_w)
        draw.line([(end_x, end_y), (p2_x, p2_y)], fill=link_color, width=link_w)
        
        # Texto del mensaje
        t_pos = msg.get('t', 0.5)
        mx = start_x + (end_x - start_x) * t_pos
        my = start_y + (end_y - start_y) * t_pos
        
        text_off = msg.get('offset', 10) * scale
        tx = mx + perp_dx * text_off
        ty = my + perp_dy * text_off
        
        lines = text.split("\n")
        total_th = len(lines) * int(12.5 * scale)
        
        for li, line in enumerate(lines):
            lbox = draw.textbbox((0, 0), line, font=font_msg)
            lw = lbox[2] - lbox[0]
            cur_tx = tx - lw / 2
            cur_ty = ty - total_th / 2 + li * int(12.5 * scale)
            
            pad_x = int(3 * scale)
            pad_y = int(1.5 * scale)
            draw.rectangle([cur_tx - pad_x, cur_ty - pad_y, cur_tx + lw + pad_x, cur_ty + int(11.5 * scale) + pad_y], 
                           fill=(255, 255, 255, 240))
            draw.text((cur_tx, cur_ty), line, fill=(35, 35, 35, 255), font=font_msg)
            
    final_img = img.resize((width, height), Image.Resampling.LANCZOS)
    final_img.save(output_path, "PNG", dpi=(300, 300))
    print(f"[OK] Diagrama generado: {output_path}")

print("Modulo de renderizado cargado correctamente.")
