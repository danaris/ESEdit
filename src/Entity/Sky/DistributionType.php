<?php

namespace App\Entity\Sky;

enum DistributionType {
	case Narrow;
	case Medium;
	case Wide;
	case Uniform;
	case Triangular;
};