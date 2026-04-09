<?php
namespace App\Datos\Config;

use PDO;
use Exception;
use Throwable;

class TransactionManager {
    public function __construct(private readonly PDO $db) {}

    /**
     * Ejecuta una lógica dentro de una transacción segura.
     * @param callable $callback Función que contiene la lógica de negocio.
     * @return mixed El resultado de la función callback.
     * @throws Throwable Si algo falla, hace rollback y lanza la excepción.
     */
    public function transaction(callable $callback): mixed {
        try {
            // Iniciamos la transacción
            $this->db->beginTransaction();

            // Ejecutamos la lógica que nos pasaron
            $result = $callback();

            // Si todo salió bien, confirmamos los cambios
            $this->db->commit();

            return $result;
        } catch (Throwable $e) {
            // Si hubo CUALQUIER error (Exception o Error de PHP), deshacemos todo
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Lanzamos el error para que el Controller lo capture
            throw $e;
        }
    }
}