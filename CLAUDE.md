# Arte y Crochet — notas para Claude Code

## Dónde está el código
- Child theme: `themes/child_hummingbird/` (montado por bind mount en el contenedor)
- Módulo propio: `modules/monitoringimage/` (bind mount)
- Edita SIEMPRE estos archivos en el proyecto, nunca dentro del contenedor.
- El núcleo de PrestaShop vive en el volumen `prestashop_html_data`: no se toca.

## Reglas
- No modificar el núcleo ni el tema padre Hummingbird: sobrescribir en el child theme.
- Nunca `git add .`: añadir archivos por nombre y revisar `git status` antes del commit.
- Secretos solo en `.env` (ignorado por Git). El compose usa `${VARIABLE}`.
- Commits con Conventional Commits (`feat:`, `fix:`, `style:`…).