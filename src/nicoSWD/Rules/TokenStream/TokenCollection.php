<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\TokenStream;

use SplObjectStorage;

final class TokenCollection extends SplObjectStorage
{
    /**
     * @return Token\BaseToken|null
     */
    public function current()
    {
        return parent::current();
    }

    public function toArray(): array
    {
        $items = [];

        foreach ($this as $item) {
            $items[] = $item->getValue();
        }

        return $items;
    }
}
