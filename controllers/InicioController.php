<?php
/**
 * ============================================================
 * CONTROLADOR: Inicio (dashboard)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Home comun de todos los roles: muestra el saludo, el rol,
 * los modulos habilitados (con su estado) y el resumen de la
 * sesion, dentro del shell (header + aside).
 */

declare(strict_types=1);

class InicioController
{
    /**
     * Accion: mostrar el dashboard (default de ?controlador=Inicio).
     */
    public function mostrar(): void
    {
        $modelo  = new Usuario();
        $usuario = $modelo->obtenerPorId((int) $_SESSION['usuario']['id_usuario']);

        if ($usuario === null || (int) $usuario['activo'] !== 1) {
            // Sesion huérfana (usuario borrado/desactivado): cerrar.
            (new UsuarioController())->cerrarSesion();
            return;
        }

        extract(contextoVista('Inicio', 'Inicio'));

        require BASE_PATH . '/views/inicio/inicio.php';
    }
}
