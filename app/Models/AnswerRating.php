<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** UC-10 / RF-25. Satu nilai per jawaban per user; user boleh mengubah nilainya. */
#[Fillable(['chat_message_id', 'user_id', 'score', 'comment'])]
class AnswerRating extends Model
{
    public const MIN_SCORE = 1;

    public const MAX_SCORE = 5;
}
