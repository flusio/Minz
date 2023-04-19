<?php

namespace Minz\Database;

/**
 * Allow to declare the database column of a class property.
 *
 * It is usually used with Recordable and Table.
 *
 *     use Minz\Database;
 *
 *     #[Database\Table(name: 'users')]
 *     class User
 *     {
 *         use Database\Recordable;
 *
 *         #[Database\Column]
 *         public int $id;
 *
 *         #[Database\Column]
 *         public \DateTimeImmutable $created_at;
 *
 *         #[Database\Column]
 *         public string $nickname;
 *     }
 *
 * A property can be declared as `computed`. This is used to load a value
 * from the database (e.g. `SELECT COUNT(posts.*) AS count_posts FROM users,
 * posts ...`) even if the column doesn't exist.
 *
 *     use Minz\Database;
 *
 *     #[Database\Table(name: 'users')]
 *     class User
 *     {
 *         // ...
 *
 *         #[Database\Column(computed: true)]
 *         public string $count_posts;
 *     }
 *
 * DateTime properties also accepts a `format` parameter. This customizes the
 * format of the datetimes in database. By default, it is set to
 * Column::DATETIME_FORMAT.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column
{
    // This is almost ISO 8601 format, but the date and time are separated with
    // a space instead of a T. This is to be as close as possible to the format
    // of PostgreSQL.
    // @see https://www.postgresql.org/docs/current/datatype-datetime.html#DATATYPE-DATETIME-OUTPUT
    public const DATETIME_FORMAT = 'Y-m-d H:i:sP';

    public bool $computed;

    public ?string $format;

    public function __construct(bool $computed = false, string $format = null)
    {
        $this->computed = $computed;
        $this->format = $format;
    }
}
