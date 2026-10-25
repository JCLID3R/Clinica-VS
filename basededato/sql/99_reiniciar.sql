-- =====================================================================
-- Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud
-- 99 - Reiniciar: borra TODO el esquema public (solo en desarrollo)
-- =====================================================================
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;
GRANT ALL ON SCHEMA public TO public;
