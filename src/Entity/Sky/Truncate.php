<?php

namespace App\Entity\Sky;

enum Truncate : int {
	case NONE = 0;
	case FRONT = 1;
	case MIDDLE = 2;
	case BACK = 3;
};