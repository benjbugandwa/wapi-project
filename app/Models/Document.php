<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use App\Models\Concerns\UsesUuid;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use BelongsToCabinet, HasFactory, SoftDeletes, UsesUuid;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cabinet_id',
        'dossier_id',
        'client_id',
        'nom_original',
        'nom_systeme',
        'chemin_acces',
        'extension',
        'type_mime',
        'taille_octets',
        'categorie',
        'est_confidentiel',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'est_confidentiel' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Client, Document>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Dossier, Document>
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    /**
     * @return BelongsTo<User, Document>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
