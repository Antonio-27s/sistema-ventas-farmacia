<?php
/**
 * ============================================================
 * MODELO: IntentoLogin
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Auditoria de accesos + bloqueo por fuerza bruta.
 * Cada intento de login (exito o fallo) queda registrado con
 * usuario, IP y fecha. La misma tabla alimenta:
 *   - el rastro forense (que quien, desde donde y cuando), y
 *   - el limite de intentos fallidos (usuario e IP).
 * Regla: TODA consulta usa prepared statements.
 */

declare(strict_types=1);

class IntentoLogin
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Registra un intento de login (exito o fallo).
     */
    public function registrar(string $usuario, string $ip, bool $exito): void
    {
        $sql = 'INSERT INTO intentos_login (usuario, ip, exito)
                VALUES (:usuario, :ip, :exito)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario' => mb_substr($usuario, 0, 50),
            ':ip'      => mb_substr($ip, 0, 45), // IPv6 maximo = 45 chars
            ':exito'   => $exito ? 1 : 0,
        ]);

        // Mantenimiento: el historial no crece indefinidamente
        $this->limpiarHistorial(30);
    }

    /**
     * Fallos recientes de un usuario dentro de la ventana,
     * contados DESDE SU ULTIMO EXITO (un login correcto corta
     * la racha de fallos; evita el auto-bloqueo de un usuario
     * legitimo que estuvo fallando antes de entrar).
     */
    public function fallosRecientesUsuario(string $usuario, int $ventanaSeg): int
    {
        $sql = 'SELECT COUNT(*)
               FROM intentos_login
               WHERE usuario = :usuario
                 AND exito = 0
                 AND fecha >= (NOW() - INTERVAL :seg SECOND)
                 AND fecha > COALESCE(
                     (SELECT MAX(fecha) FROM intentos_login
                      WHERE usuario = :usuario2 AND exito = 1),
                     \'1970-01-01 00:00:00\')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario'  => $usuario,
            ':usuario2' => $usuario,
            ':seg'      => $ventanaSeg,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fallos recientes desde una IP dentro de la ventana
     * (frena escaneos masivos: muchas cuentas desde un IP).
     */
    public function fallosRecientesIp(string $ip, int $ventanaSeg): int
    {
        $sql = 'SELECT COUNT(*)
               FROM intentos_login
               WHERE ip = :ip
                 AND exito = 0
                 AND fecha >= (NOW() - INTERVAL :seg SECOND)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ip'  => $ip,
            ':seg' => $ventanaSeg,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Borra el historial anterior a N dias (30 por defecto).
     */
    public function limpiarHistorial(int $dias): void
    {
        $sql = 'DELETE FROM intentos_login
                WHERE fecha < (NOW() - INTERVAL :dias DAY)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':dias' => $dias]);
    }

    /**
     * Ultimo acceso EXITOSO de cada usuario (auditoria):
     * mapa usuario => 'Y-m-d H:i:s' (vacio si no hay exitos).
     *
     * @return array<string, string>
     */
    public function ultimosExitos(): array
    {
        $sql = 'SELECT usuario, MAX(fecha) AS ultimo
               FROM intentos_login
               WHERE exito = 1
               GROUP BY usuario';

        $stmt = $this->pdo->query($sql);
        $mapa = [];

        foreach ($stmt->fetchAll() as $fila) {
            $mapa[(string) $fila['usuario']] = (string) $fila['ultimo'];
        }

        return $mapa;
    }
}
