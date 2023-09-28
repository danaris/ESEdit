<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class Format {

	// Format an integer value, inserting its digits into the given string in
	// reverse order and then reversing the string.
	public static function FormatInteger(int $value, bool $isNegative, string &$result) {
		$places = 0;
		$zero = mb_ord('0');
		do {
			if ($places && !($places % 3)) {
				$result .= ',';
			}
			$places++;

			$result .= mb_chr($zero + $value % 10);
			$value /= 10;
		} while($value);

		if ($isNegative) {
			$result .= '-';
		}

		$result = strrev($result);
	}
	// 
	// 	// Helper function for ExpandConditions.
	// 	//
	// 	// source.substr(formatStart, formatSize) contains the format (credits, mass, etc)
	// 	// source.substr(conditionStart, conditionSize) contains the condition name
	// 	//
	// 	// If formatStart or formatSize are string::npos, then there is no formatting.
	// 	//
	// 	// The getter() acts like ConditionsStore.Get(), providing condition values.
	// 	// These are passed through Format::Whatever(), and appended to the result.
	// 	void AppendCondition(string &result, const string &source, Format::ConditionGetter getter,
	// 		size_t formatStart, size_t formatSize, size_t conditionStart, size_t conditionSize)
	// 	{
	// 		int64_t value = getter(source, conditionStart, conditionSize);
	// 
	// 		auto IsFormat = [&source, formatStart, formatSize](const char *format)
	// 		{
	// 			return !source.compare(formatStart, formatSize, format);
	// 		};
	// 
	// 		if(formatStart == string::npos || formatSize == string::npos)
	// 			result.append(Format::Number(value));
	// 		else if(IsFormat("raw"))
	// 			result.append(to_string(value));
	// 		else if(IsFormat("credits"))
	// 			result.append(Format::CreditString(value)); // 1 credit, 2 credits, etc.
	// 		else if(IsFormat("scaled"))
	// 			result.append(Format::Credits(value)); // 35, 35k, 35M, etc.
	// 		else if(IsFormat("tons"))
	// 			result.append(Format::MassString(value)); // X tons or X ton
	// 		else if(IsFormat("playtime"))
	// 			result.append(Format::PlayTime(value)); // 3d 19h 24m 8s
	// 		else
	// 			// "number" or unsupported format
	// 			result.append(Format::Number(value));
	// 	}
	// }
	// 
	// 
	// 
	// Convert the given number into abbreviated format with a suffix like
	// "M" for million, "B" for billion, or "T" for trillion. Any number
	// above 1 quadrillion is instead shown in scientific notation.
	public static function Credits(int $value): string {
		$isNegative = ($value < 0);
		$absolute = abs($value);
	
		// If the value is above one quadrillion, show it in scientific notation.
		if ($absolute > 1000000000000000) {
			$out = sprintf("%.3e", $value);
			return $out;
		}
	
		// Reserve enough space for something like "-123.456M".
		$result = '';
	
		// Handle numbers bigger than a million.
		$suffix = ['T', 'B', 'M'];
		$threshold = [1000000000000, 1000000000, 1000000];
		for ($i = 0; $i < count($suffix); $i++) {
			if ($absolute > $threshold[$i]) {
				$result .= $suffix[$i];
				$decimals = ($absolute / ($threshold[$i] / 1000)) % 1000;
				$result .= $decimals;
				// for ($d = 0; $d < 3; $d++) {
				// 	$result .= static_cast<char>('0' + decimals % 10);
				// 	decimals /= 10;
				// }
				$result .= '.';
				$absolute /= $threshold[$i];
				break;
			}
		}

		// Convert the number to a string, adding commas if needed.
		self::FormatInteger($absolute, $isNegative, $result);
		return $result;
	}

	// Convert the given number into abbreviated format as described in Format::Credits,
	// then attach the ' credit' or ' credits' suffix to it.
	public static function CreditString(int $value): string {
		if ($value == 1) {
			return "1 credit";
		} else {
			return self::Credits($value) + " credits";
		}
	}
	// 
	// 
	// 
	// // Writes the given number into a string,
	// // then attach the ' ton' or ' tons' suffix to it.
	// string Format::MassString(double amount)
	// {
	// 	if(amount == 1)
	// 		return "1 ton";
	// 	return Format::Number(amount) + " tons";
	// }
	// 
	// 
	// 
	// // Creates a string similar to '<amount> tons of <cargo>'.
	// string Format::CargoString(double amount, const string &cargo)
	// {
	// 	return MassString(amount) + " of " + cargo;
	// }
	// 
	// 
	// 
	// // Convert a time in seconds to years/days/hours/minutes/seconds
	// string Format::PlayTime(double timeVal)
	// {
	// 	string result;
	// 	int timeValFormat = 0;
	// 	static const array<char, 5> SUFFIX = {'s', 'm', 'h', 'd', 'y'};
	// 	static const array<int, 4> PERIOD = {60, 60, 24, 365};
	// 
	// 	timeValFormat = max(0., timeVal);
	// 	// Break time into larger and larger units until the largest one, or the value is empty
	// 	size_t i = 0;
	// 	do {
	// 		int period = (i < SUFFIX.size() - 1 ? timeValFormat % PERIOD[i] : timeValFormat);
	// 		result = (i == 0 ? result + SUFFIX[i] : result + ' ' + SUFFIX[i]);
	// 		do {
	// 			result += static_cast<char>('0' + period % 10);
	// 			period /= 10;
	// 		} while(period);
	// 		if(i < PERIOD.size())
	// 			timeValFormat /= PERIOD[i];
	// 		i++;
	// 	} while (timeValFormat && i < SUFFIX.size());
	// 
	// 	reverse(result.begin(), result.end());
	// 	return result;
	// }
	// 
	// 
	// 
	// // Convert the given number to a string, with a reasonable number of decimal
	// // places. (This is primarily for displaying ship and outfit attributes.)
	// string Format::Number(double value)
	// {
	// 	if(!value)
	// 		return "0";
	// 
	// 	string result;
	// 	bool isNegative = (value < 0.);
	// 	value = fabs(value);
	// 
	// 	// Only show decimal places for numbers between +/-10'000.
	// 	double decimal = modf(value, &value);
	// 	if(decimal && value < 10000)
	// 	{
	// 		double tenths = 0.;
	// 		// Account for floating-point representation error by adding EPS after multiplying.
	// 		constexpr double EPS = 0.0000000001;
	// 		int hundredths = static_cast<int>(EPS + 10. * modf(decimal * 10., &tenths));
	// 		if(hundredths > 9)
	// 		{
	// 			hundredths = 0;
	// 			++tenths;
	// 		}
	// 		if(tenths >= 10. - EPS)
	// 		{
	// 			++value;
	// 			tenths = hundredths = 0;
	// 		}
	// 
	// 		// Values up to 1000 may have two decimal places.
	// 		bool two = value < 1000 && hundredths;
	// 		if(two)
	// 			result += static_cast<char>('0' + hundredths);
	// 		if(two || tenths)
	// 		{
	// 			result += static_cast<char>('0' + tenths);
	// 			result += '.';
	// 		}
	// 	}
	// 
	// 	// Convert the number to a string, adding commas if needed.
	// 	FormatInteger(value, isNegative, result);
	// 	return result;
	// }
	// 
	// 
	// 
	// // Format the given value as a number with exactly the given number of
	// // decimal places (even if they are all 0).
	// string Format::Decimal(double value, int places)
	// {
	// 	double integer;
	// 	double fraction = fabs(modf(value, &integer));
	// 
	// 	string result = to_string(static_cast<int>(integer)) + ".";
	// 	while(places--)
	// 	{
	// 		fraction = modf(fraction * 10., &integer);
	// 		result += ('0' + static_cast<int>(integer));
	// 	}
	// 	return result;
	// }
	// 
	// 
	// 
	// // Convert a string into a number. As with the output of Number(), the
	// // string can have suffixes like "M", "B", etc.
	// // It can also contain spaces or "," as separators like 1,000 or 1 000.
	// double Format::Parse(const string &str)
	// {
	// 	double place = 1.;
	// 	double value = 0.;
	// 
	// 	string::const_iterator it = str.begin();
	// 	string::const_iterator end = str.end();
	// 	while(it != end && (*it < '0' || *it > '9') && *it != '.')
	// 		++it;
	// 
	// 	for( ; it != end; ++it)
	// 	{
	// 		if(*it == '.')
	// 			place = .1;
	// 		else if(*it == ',' || *it == ' ') {}
	// 		else if(*it < '0' || *it > '9')
	// 			break;
	// 		else
	// 		{
	// 			double digit = *it - '0';
	// 			if(place < 1.)
	// 			{
	// 				value += digit * place;
	// 				place *= .1;
	// 			}
	// 			else
	// 			{
	// 				value *= 10.;
	// 				value += digit;
	// 			}
	// 		}
	// 	}
	// 
	// 	if(it != end)
	// 	{
	// 		if(*it == 'k' || *it == 'K')
	// 			value *= 1e3;
	// 		else if(*it == 'm' || *it == 'M')
	// 			value *= 1e6;
	// 		else if(*it == 'b' || *it == 'B')
	// 			value *= 1e9;
	// 		else if(*it == 't' || *it == 'T')
	// 			value *= 1e12;
	// 	}
	// 
	// 	return value;
	// }
	// 
	// 
	// 
	public static function Replace(string &$source, array &$keys): string {
		$result = '';
	
		$start = 0;
		$search = $start;
		while ($search < strlen($source)) {
			$left = strpos($source, '<', $search);
			if ($left === false) {
				break;
			}

			$right = strpos($source, '>', $left);
			if ($right === false) {
				break;
			}
	
			$matched = false;
			$right++;
			$length = $right - $left;
			foreach ($keys as $from => $to) {
				if (substr($from, $left, $length) == $source) {
					$result .= substr($source, $start, $left - $start);
					$result .= $to;
					$start = $right;
					$search = $start;
					$matched = true;
					break;
				}
			}
	
			if (!$matched)
				$search = $left + 1;
		}
	
		$result .= substr($source, $start, strlen($source) - $start);
		return $result;
	}

	public static function ReplaceAll(string &$text, string $target, string $replacement) {
		// If the searched string is an empty string, do nothing.
		if ($target == '') {
			return;
		}
	
		$newString = '';
	
		// Index at which to begin searching for the target string.
		$start = 0;
		$matchLength = strlen($target);
		// Index at which the target string was found.
		$findPos = strlen($text);
		while (($findPos = strpos($text, $target, $start)) !== false) {
			$newString .= substr($text, $start, $findPos - $start);
			$newString .= $replacement;
			$start = $findPos + $matchLength;
		}
	
		// Add the remaining text.
		$newString .= substr($text, $start);
	
		return $newString;
	}

	public static function Capitalize(string $str): string {
		$result = $str;
		$first = true;
		for ($i=0; $i<strlen($result); $i++) {
			$c = $result[$i];
			if ($c == ' ') {
				$first = true;
			} else {
				if ($first && $c == strtolower($c)) {
					$c = strtoupper($c);
				}
				$first = false;
			}
		}
		return $result;
	}
	// 
	// 
	// 
	// string Format::LowerCase(const string &str)
	// {
	// 	string result = str;
	// 	for(char &c : result)
	// 		c = tolower(c);
	// 	return result;
	// }
	// 
	// 
	// 
	// // Split a single string into substrings with the given separator.
	// vector<string> Format::Split(const string &str, const string &separator)
	// {
	// 	vector<string> result;
	// 	size_t begin = 0;
	// 	while(true)
	// 	{
	// 		size_t pos = str.find(separator, begin);
	// 		if(pos == string::npos)
	// 			pos = str.length();
	// 		result.emplace_back(str, begin, pos - begin);
	// 		begin = pos + separator.size();
	// 		if(begin >= str.length())
	// 			break;
	// 	}
	// 	return result;
	// }
	// 
	// 
	// 
	// string Format::ExpandConditions(const string &source, ConditionGetter getter)
	// {
	// 	// Optimization for most common case: no conditions
	// 	if(source.find('&') == string::npos)
	// 		return source;
	// 
	// 	string result;
	// 	result.reserve(source.size());
	// 
	// 	size_t formatStart = string::npos;
	// 	size_t formatSize = string::npos;
	// 	size_t conditionStart = string::npos;
	// 	size_t conditionSize = string::npos;
	// 
	// 	// Hand-coded regular grammar parser for:
	// 	//	&[format@condition]
	// 	//	&[condition]
	// 	// Using these states:
	// 	//	state = _ ----- outside of all &[] regions
	// 	//	state = & ----- just read & and hoping to see a [
	// 	//	state = [ ----- read &[ but haven't seen @ or ] yet
	// 	//	state = N ----- inside a nested [] of depth `depth`
	// 	//	state = @ ----- read &[...@ but haven't seen ] yet. Have format start & size.
	// 
	// 	// Anything inside a &[...] is sent to AppendCondition
	// 
	// 	static const char OUTER = '_';
	// 	static const char PREFIX = '&';
	// 	static const char LPAREN = '[';
	// 	static const char RPAREN = ']';
	// 	static const char DIVIDER = '@';
	// 	static const char NESTED = 'N';
	// 
	// 	char state = OUTER;
	// 	// Depth of nested [] within the &[...]
	// 	int depth = 0;
	// 	// State before entering the nested []
	// 	char oldState = LPAREN;
	// 	// "start" is the beginning of the text that has not yet been sent to result.
	// 	size_t start = 0;
	// 	for(size_t look = 0; look < source.size(); ++look)
	// 	{
	// 		char next = source[look];
	// 		// This would be faster with a nested select, but that would be
	// 		// harder to read, and I don't expect this to be performance-critical.
	// 		if(state == OUTER && next == PREFIX)
	// 		{
	// 			if(look > start)
	// 			{
	// 				result.append(source, start, look - start);
	// 				start = look;
	// 			}
	// 			state = PREFIX;
	// 		}
	// 		else if(state == OUTER || (state == PREFIX && next != LPAREN))
	// 			// Accumulate one character to print outside of any &[@]
	// 			state = OUTER;
	// 		else if(state == PREFIX && next == LPAREN)
	// 		{
	// 			formatStart = formatSize = conditionStart = conditionSize = string::npos;
	// 			state = LPAREN;
	// 		}
	// 		else if(state == LPAREN && next == DIVIDER)
	// 		{
	// 			formatStart = start + 2;
	// 			formatSize = look - formatStart;
	// 			state = DIVIDER;
	// 		}
	// 		else if(state == DIVIDER && next == RPAREN)
	// 		{
	// 			conditionStart = formatStart + formatSize + 1;
	// 			conditionSize = look - conditionStart;
	// 			AppendCondition(result, source, getter, formatStart, formatSize,
	// 				conditionStart, conditionSize);
	// 			start = look + 1;
	// 			state = OUTER;
	// 		}
	// 		else if((state == LPAREN || state == DIVIDER) && next == LPAREN)
	// 		{
	// 			oldState = state;
	// 			state = NESTED;
	// 			depth = 1;
	// 		}
	// 		else if(state == NESTED && next == LPAREN)
	// 			depth++;
	// 		else if(state == NESTED && next == RPAREN && depth > 1)
	// 			depth--;
	// 		else if(state == NESTED && next == RPAREN && depth == 1)
	// 			state = oldState;
	// 		else if(state == LPAREN && next == RPAREN)
	// 		{
	// 			conditionStart = start + 2;
	// 			conditionSize = look - conditionStart;
	// 			AppendCondition(result, source, getter, formatStart, formatSize,
	// 				conditionStart, conditionSize);
	// 			start = look + 1;
	// 			state = OUTER;
	// 		}
	// 		else if(!(state == LPAREN || state == DIVIDER || state == NESTED))
	// 		{
	// 			// Error in format string.
	// 			result.append(source, start, look - start + 1);
	// 			start = look + 1;
	// 			state = OUTER;
	// 		}
	// 	}
	// 	if(start < source.size())
	// 		result.append(source, start, string::npos);
	// 	return result;
	// }

}