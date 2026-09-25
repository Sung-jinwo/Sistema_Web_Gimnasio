<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    protected $table = 'comisiones';

    protected $primaryKey = 'id_comision';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'fecha_aprobacion' => 'datetime',
        'fecha_habilitacion' => 'datetime',
    ];

    public const ESTADO_ESPERANDO_PAGO = 'esperando_pago';

    public const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_OBSERVADA = 'observada';

    public const ESTADO_LIQUIDADA = 'liquidada';

    public const ESTADO_ANULADA = 'anulada';

    /**
     * Estados en los que la penalización sigue recalculándose.
     * Al habilitarse (último abono) el importe queda congelado.
     */
    public const ESTADOS_MUTABLES = [
        self::ESTADO_ESPERANDO_PAGO,
    ];

    /**
     * Estados que bloquean la aprobación final de la caja de revisión.
     */
    public const ESTADOS_BLOQUEAN_CIERRE = [
        self::ESTADO_PENDIENTE_REVISION,
        self::ESTADO_OBSERVADA,
    ];

    protected static function booted(): void
    {
        static::creating(function (Comision $comision) {
            $comision->monto ??= $comision->comision_base ?? 0;
        });
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'fkventa', 'id_venta');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'fkcaja', 'id_caja');
    }

    public function aprobadaPor()
    {
        return $this->belongsTo(User::class, 'aprobada_por', 'id');
    }

    public function getEstadoFormatoAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_ESPERANDO_PAGO => 'Esperando pago',
            self::ESTADO_PENDIENTE_REVISION => 'Pendiente de revisión',
            self::ESTADO_APROBADA => 'Aprobada',
            self::ESTADO_OBSERVADA => 'Observada',
            self::ESTADO_LIQUIDADA => 'Liquidada',
            self::ESTADO_ANULADA => 'Anulada',
            default => ucfirst($this->estado ?? ''),
        };
    }

    public function getFechaAcordadaFormatoAttribute(): string
    {
        return $this->fecha_acordada_pago ? \Carbon\Carbon::parse($this->fecha_acordada_pago)->format('d/m/Y') : '-';
    }

    public function getFechaPagoRealFormatoAttribute(): string
    {
        return $this->fecha_pago_real ? \Carbon\Carbon::parse($this->fecha_pago_real)->format('d/m/Y') : '-';
    }

    public function getDiasRetrasoAttribute(): int
    {
        if (! $this->fecha_acordada_pago || ! $this->fecha_pago_real) {
            return 0;
        }

        return max(0, \Carbon\Carbon::parse($this->fecha_acordada_pago)->diffInDays($this->fecha_pago_real));
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE_REVISION);
    }

    public function scopeLiquidadas($query)
    {
        return $query->where('estado', self::ESTADO_LIQUIDADA);
    }

    public function scopeHabilitadas($query)
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE_REVISION, self::ESTADO_OBSERVADA]);
    }

    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->where('fkuser', $usuarioId);
    }
}
