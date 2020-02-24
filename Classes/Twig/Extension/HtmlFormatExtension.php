<?php

/*
 * Twig extension for TYPO3 CMS
 * Copyright (C) 2020 CARL von CHIARI GmbH
 *
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Cvc\Typo3\CvcTwig\Twig\Extension;

use Cvc\Typo3\CvcTwig\Utility\Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @internal
 */
class HtmlFormatExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('t3_html', [Html::class, 'format'], [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
