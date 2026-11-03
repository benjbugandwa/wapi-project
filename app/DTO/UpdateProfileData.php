<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class UpdateProfileData
{
    public function __construct(
        public string $nom,
        public ?string $phoneNumber = null,
        public ?string $profilePhotoPath = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        return [
            'nom' => $this->nom,
            'phone_number' => $this->phoneNumber,
            'profile_photo_path' => $this->profilePhotoPath,
        ];
    }
}
