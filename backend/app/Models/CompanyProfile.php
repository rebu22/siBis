<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** RF-01/RF-17: konten profil perusahaan. Satu baris saja (CMS mengedit baris ini). */
#[Fillable(['name', 'vision', 'mission', 'services', 'contact_email', 'contact_phone', 'address'])]
class CompanyProfile extends Model
{
    /** ponytail: asumsinya selalu ada tepat satu baris; dibuat oleh seeder pertama kali. */
    public static function current(): ?self
    {
        return static::query()->orderBy('id')->first();
    }
}
