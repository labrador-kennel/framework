<?php declare(strict_types=1);

namespace Labrador\Validation;

use Attribute;
use Respect\Validation\Validatable;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Validate {

    public MessageGenerator $messageGenerator;

    public function __construct(
        public Validatable $rule,
        string|MessageGenerator $message = null
    ) {
        if ($message === null) {
            $this->messageGenerator = new DefaultMessageGenerator();
        } elseif ($message instanceof MessageGenerator) {
            $this->messageGenerator = $message;
        } else {
            $this->messageGenerator = new StringMessageGenerator($message);
        }
    }
}
