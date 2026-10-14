<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Coleções sugeridas no campo "Coleção" do pop-up de carta
    |--------------------------------------------------------------------------
    |
    | Chave = chave do jogo (tabela jogos_carta). São só sugestões (datalist):
    | o campo continua aceitando qualquer texto. Mais recentes primeiro.
    | Jogos cadastrados em Configurações que não estão aqui ficam sem sugestão.
    |
    */

    'colecoes' => [

        'pokemon' => [
            'Phantasmal Flames',
            'Mega Evolution',
            'White Flare',
            'Black Bolt',
            'Destined Rivals',
            'Journey Together',
            'Prismatic Evolutions',
            'Surging Sparks',
            'Stellar Crown',
            'Shrouded Fable',
            'Twilight Masquerade',
            'Temporal Forces',
            'Paldean Fates',
            'Paradox Rift',
            '151',
            'Obsidian Flames',
            'Paldea Evolved',
            'Scarlet & Violet',
            'Crown Zenith',
            'Silver Tempest',
            'Lost Origin',
            'Pokémon GO',
            'Astral Radiance',
            'Brilliant Stars',
            'Fusion Strike',
            'Evolving Skies',
        ],

        'magic' => [
            'Avatar: The Last Airbender',
            "Marvel's Spider-Man",
            'Edge of Eternities',
            'Final Fantasy',
            'Tarkir: Dragonstorm',
            'Aetherdrift',
            'Innistrad Remastered',
            'Foundations',
            'Duskmourn: House of Horror',
            'Bloomburrow',
            'Modern Horizons 3',
            'Outlaws of Thunder Junction',
            'Murders at Karlov Manor',
            'The Lost Caverns of Ixalan',
            'Wilds of Eldraine',
            'The Lord of the Rings: Tales of Middle-earth',
            'March of the Machine',
            'Phyrexia: All Will Be One',
            "The Brothers' War",
            'Dominaria United',
        ],

        'onepiece' => [
            'OP-11 A Fist of Divine Speed',
            'OP-10 Royal Blood',
            'OP-09 Emperors in the New World',
            'OP-08 Two Legends',
            'OP-07 500 Years in the Future',
            'OP-06 Wings of the Captain',
            'OP-05 Awakening of the New Era',
            'OP-04 Kingdoms of Intrigue',
            'OP-03 Pillars of Strength',
            'OP-02 Paramount War',
            'OP-01 Romance Dawn',
            'EB-02 Anime 25th Collection',
            'EB-01 Memorial Collection',
            'PRB-01 Premium Booster',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Raridades sugeridas no campo "Raridade" do pop-up de carta
    |--------------------------------------------------------------------------
    |
    | Mesmo esquema das coleções, da mais comum para a mais rara, com a sigla
    | e o símbolo impresso na carta. Jogos sem lista própria usam "padrao".
    |
    */

    'raridades' => [

        'padrao' => [
            'Comum',
            'Incomum',
            'Rara',
            'Rara Holo',
            'Ultra Rara',
            'Rara Secreta',
        ],

        // símbolos da era Scarlet & Violet (as estrelas mudam de cor)
        'pokemon' => [
            'Comum (C) ●',
            'Incomum (U) ◆',
            'Rara (R) ★',
            'Rara Holo (R) ★',
            'Rara Dupla (RR) ★★',
            'Ilustração Rara (AR) ★ dourada',
            'Ultra Rara (SR) ★★ prateada',
            'Ilustração Rara Especial (SAR) ★★ dourada',
            'Hiper Rara (UR) ★★★ dourada',
            'Rara Brilhante (S)',
            'Ultra Rara Brilhante (SSR)',
            'ACE SPEC (ACE)',
            'Promo (PR)',
        ],

        'magic' => [
            'Comum (C)',
            'Incomum (U)',
            'Rara (R)',
            'Mítica Rara (M)',
            'Especial (S)',
            'Bônus (B)',
        ],

        'onepiece' => [
            'Comum (C)',
            'Incomum (UC)',
            'Rara (R)',
            'Super Rara (SR)',
            'Rara Secreta (SEC)',
            'Líder (L)',
            'Especial (SP)',
            'Rara Tesouro (TR)',
            'Arte Alternativa (AA)',
            'Manga Rare',
            'Promo (P)',
        ],

    ],

];
