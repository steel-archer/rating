<?php

declare(strict_types=1);

namespace App\Classic\Enum;

/**
 * How close the results submission deadline is, used to highlight the deadline
 * hint on the session claims list. Computed from the number of whole days left
 * until the deadline.
 */
enum SubmissionDeadlineUrgency: string
{
    /** Deadline is far away (more than three days left). */
    case Calm = 'calm';

    /** Deadline is near (two or three days left). */
    case Soon = 'soon';

    /** Today is the last day results can be submitted (one day left). */
    case LastDay = 'last_day';
}
