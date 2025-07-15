<?php

namespace App\Session;

use Symfony\Component\Validator\Constraints as Assert;

class JoinSessionRequest
{
    #[Assert\NotBlank(message: 'Le pseudo est requis')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Le pseudo doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_\-\s]+$/',
        message: 'Le pseudo ne peut contenir que des lettres, chiffres, espaces, traits d\'union et tirets bas'
    )]
    public string $pseudo = '';

    #[Assert\NotBlank(message: 'Le code de session est requis')]
    #[Assert\Length(
        min: 8,
        max: 8,
        exactMessage: 'Le code de session doit contenir exactement {{ limit }} caractères'
    )]
    public string $sessionCode = '';
}
