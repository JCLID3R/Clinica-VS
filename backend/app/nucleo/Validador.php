<?php
/**
 * Validador: validaciones de datos en el servidor (las del navegador se pueden saltar).
 * Uso en un schemas.php:
 *     $v = new Validador($datos);
 *     $v->requerido('nombres')->texto('nombres', 60)->entero('ci')->fecha('fecha_nacimiento', pasada: true);
 *     $v->verificar();   // lanza 400 con los errores por campo
 */
declare(strict_types=1);

namespace App\Nucleo;

final class Validador
{
    private array $errores = [];

    public function __construct(private readonly array $datos)
    {
    }

    public function requerido(string ...$campos): self
    {
        foreach ($campos as $c) {
            if (!isset($this->datos[$c]) || (is_string($this->datos[$c]) && trim($this->datos[$c]) === '')) {
                $this->errores[$c] = 'Campo obligatorio.';
            }
        }
        return $this;
    }

    public function texto(string $campo, int $maximo): self
    {
        if (isset($this->datos[$campo]) && mb_strlen((string) $this->datos[$campo]) > $maximo) {
            $this->errores[$campo] = "Máximo {$maximo} caracteres.";
        }
        return $this;
    }

    public function entero(string $campo, int $minimo = 1): self
    {
        if (isset($this->datos[$campo]) && filter_var($this->datos[$campo], FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimo]]) === false) {
            $this->errores[$campo] = 'Debe ser un número entero válido.';
        }
        return $this;
    }

    public function correo(string $campo): self
    {
        if (!empty($this->datos[$campo]) && !filter_var($this->datos[$campo], FILTER_VALIDATE_EMAIL)) {
            $this->errores[$campo] = 'Correo electrónico inválido.';
        }
        return $this;
    }

    public function fecha(string $campo, bool $pasada = false, bool $futura = false): self
    {
        if (empty($this->datos[$campo])) {
            return $this;
        }
        $f = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $this->datos[$campo]);
        if (!$f) {
            $this->errores[$campo] = 'Fecha inválida (AAAA-MM-DD).';
        } elseif ($pasada && $f > new \DateTimeImmutable('today')) {
            $this->errores[$campo] = 'La fecha no puede ser futura.';
        } elseif ($futura && $f < new \DateTimeImmutable('today')) {
            $this->errores[$campo] = 'No se permiten fechas pasadas.';
        }
        return $this;
    }

    public function hora(string $campo): self
    {
        if (!empty($this->datos[$campo]) && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $this->datos[$campo])) {
            $this->errores[$campo] = 'Hora inválida (HH:MM).';
        }
        return $this;
    }

    public function enLista(string $campo, array $permitidos): self
    {
        if (isset($this->datos[$campo]) && !in_array($this->datos[$campo], $permitidos, true)) {
            $this->errores[$campo] = 'Valor no permitido.';
        }
        return $this;
    }

    public function verificar(): void
    {
        if ($this->errores) {
            throw new ErrorApi(400, 'Datos inválidos.', $this->errores);
        }
    }
}
