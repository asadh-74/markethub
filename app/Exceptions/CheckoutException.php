<?php

namespace App\Exceptions;

use RuntimeException;

/** A problem the shopper can understand and fix (empty cart, out of stock...). */
class CheckoutException extends RuntimeException
{
}
