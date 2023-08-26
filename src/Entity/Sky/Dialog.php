<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class Dialog {
	
	// WrappedText text;
	// int height;
	// 
	// std::function<void(int)> intFun;
	// std::function<void(const std::string &)> stringFun;
	// std::function<void()> voidFun;
	// std::function<bool(const std::string &)> validateFun;
	// 
	// bool canCancel;
	// bool okIsActive;
	// bool isMission;
	// bool isOkDisabled = false;
	// bool allowsFastForward = false;
	// 
	// std::string input;
	// 
	// Point okPos;
	// Point cancelPos;
	// 
	// const System *system = nullptr;
	// PlayerInfo *player = nullptr;
	
	// Format and add the text from the given node to the given string.
	public static function ParseTextNode(DataNode $node, int $startingIndex, string &$text) {
		for ($i = $startingIndex; $i < $node->size(); ++$i) {
			if ($text != '') {
				$text .= "\n\t";
			}
			$text .= $node->getToken($i);
		}
		foreach ($node as $child) {
			for ($i = 0; $i < $child->size(); ++$i) {
				if ($text != '') {
					$text .= "\n\t";
				}
				$text .= $child->getToken($i);
			}
		}
	}
}