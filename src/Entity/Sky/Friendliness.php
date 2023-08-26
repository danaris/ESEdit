<?php

namespace App\Entity\Sky;

enum Friendliness : int {
	case FRIENDLY = 1;
	case RESTRICTED = 2;
	case HOSTILE = 3;
	case DOMINATED = 4;
}