<?php

namespace App\Entity;

class Files {
	public static string $resources;
	public static string $config;
	
	public static string $dataPath = '';
	public static string $imagePath = '';
	public static string $soundPath = '';
	public static string $savePath = '';
	public static string $testPath = '';
	
	public static function Init(string $baseDir, string $configDir): void {
	
		if (self::$resources == '') {
			// Find the path to the resource directory. 
	
			self::$resources = $baseDir;
		}
		
		if (substr(self::$resources, -1) != '/') {
			self::$resources .= '/';
		}
		
		self::$dataPath = self::$resources . "data/";
		self::$imagePath = self::$resources . "images/";
		self::$soundPath = self::$resources . "sounds/";
	
		if (self::$config == '') {
			self::$config = $configDir;
		}
		
		if (substr(self::$config, -1) != '/') {
			self::$config .= '/';
		}
		
		self::$savePath = $configDir . "saves/";
	}
	
	public static function Resources(): string {
		return self::$resources;
	}
	
	public static function Config(): string {
		return self::$config;
	}
	
	public static function Data(): string {
		return self::$dataPath;
	}
	
	public static function Images(): string {
		return self::$imagePath;
	}
	
	public static function Sounds(): string {
		return self::$soundPath;
	}
	
	public static function Saves(): string {
		return self::$savePath;
	}
	
	// const string &Files::Tests()
	// {
	// 	return testPath;
	// }
	
	public static function List(string $directory): array { // vector<string>
		if ($directory == '' || substr($directory,-1) != '/') {
			$directory .= '/';
		}
	
		$list = [];
	
		if (!file_exists($directory)) {
			return $list;
		}
		
		$dirContents = scandir($directory);
		
		foreach ($dirContents as $entry) {
			if ($entry[0] == '.') {
				continue;
			}
			$path = $directory . $entry;
			if (is_file($path)) {
				$list []= $path;
			}
		}
		
		sort($list);
		
		return $list;
	}
	
	// Get a list of any directories in the given directory.
	public static function ListDirectories(string $directory): array { // vector<string>
		if ($directory == '' || substr($directory,-1) != '/') {
			$directory .= '/';
		}
	
		$list = [];
	
		if (!file_exists($directory)) {
			return $list;
		}
		
		$dirContents = scandir($directory);
		
		foreach ($dirContents as $entry) {
			if ($entry[0] == '.') {
				continue;
			}
			$path = $directory . $entry;
			if (is_dir($path)) {
				$list []= $path;
			}
		}
		
		sort($list);
		
		return $list;
	}
	
	public static function RecursiveList(string $directory) {
		
		if ($directory == '' || substr($directory,-1) != '/') {
			$directory .= '/';
		}
		
		$list = [];
		
		if (!file_exists($directory)) {
			return $list;
		}
		
		$dirContents = scandir($directory);
		
		foreach ($dirContents as $entry) {
			if ($entry[0] == '.') {
				continue;
			}
			$path = $directory . $entry;
			
			if (is_file($path)) {
				$list []= $path;
			} else if (is_dir($path)) {
				$list = array_merge(self::RecursiveList($path . '/'), $list);
			}
			
		}
		
		sort($list);
		
		return $list;
	}
	// 
	// 
	// 
	// bool Files::Exists(const string &filePath)
	// {
	// #if defined _WIN32
	// 	struct _stat buf;
	// 	return !_wstat(Utf8::ToUTF16(filePath).c_str(), &buf);
	// #else
	// 	struct stat buf;
	// 	return !stat(filePath.c_str(), &buf);
	// #endif
	// }
	// 
	// 
	// 
	// time_t Files::Timestamp(const string &filePath)
	// {
	// #if defined _WIN32
	// 	struct _stat buf;
	// 	_wstat(Utf8::ToUTF16(filePath).c_str(), &buf);
	// #else
	// 	struct stat buf;
	// 	stat(filePath.c_str(), &buf);
	// #endif
	// 	return buf.st_mtime;
	// }
	// 
	// 
	// 
	// void Files::Copy(const string &from, const string &to)
	// {
	// #if defined _WIN32
	// 	CopyFileW(Utf8::ToUTF16(from).c_str(), Utf8::ToUTF16(to).c_str(), false);
	// #else
	// 	Write(to, Read(from));
	// 	// Preserve the timestamps of the original file.
	// 	struct stat buf;
	// 	if(stat(from.c_str(), &buf))
	// 		Logger::LogError("Error: Cannot stat \"" + from + "\".");
	// 	else
	// 	{
	// 		struct utimbuf times;
	// 		times.actime = buf.st_atime;
	// 		times.modtime = buf.st_mtime;
	// 		if(utime(to.c_str(), &times))
	// 			Logger::LogError("Error: Failed to preserve the timestamps for \"" + to + "\".");
	// 	}
	// #endif
	// }
	// 
	// 
	// 
	// void Files::Move(const string &from, const string &to)
	// {
	// #if defined _WIN32
	// 	MoveFileExW(Utf8::ToUTF16(from).c_str(), Utf8::ToUTF16(to).c_str(), MOVEFILE_REPLACE_EXISTING);
	// #else
	// 	rename(from.c_str(), to.c_str());
	// #endif
	// }
	// 
	// 
	// 
	// void Files::Delete(const string &filePath)
	// {
	// #if defined _WIN32
	// 	DeleteFileW(Utf8::ToUTF16(filePath).c_str());
	// #else
	// 	unlink(filePath.c_str());
	// #endif
	// }
	// 
	// 
	// 
	// // Get the filename from a path.
	// string Files::Name(const string &path)
	// {
	// 	// string::npos = -1, so if there is no '/' in the path this will
	// 	// return the entire string, i.e. path.substr(0).
	// 	return path.substr(path.rfind('/') + 1);
	// }
	// 
	// 
	// 
	// FILE *Files::Open(const string &path, bool write)
	// {
	// #if defined _WIN32
	// 	FILE *file = nullptr;
	// 	_wfopen_s(&file, Utf8::ToUTF16(path).c_str(), write ? L"w" : L"rb");
	// 	return file;
	// #else
	// 	return fopen(path.c_str(), write ? "wb" : "rb");
	// #endif
	// }
	// 
	// 
	// 
	// string Files::Read(const string &path)
	// {
	// 	File file(path);
	// 	return Read(file);
	// }
	// 
	// 
	// 
	// string Files::Read(FILE *file)
	// {
	// 	string result;
	// 	if(!file)
	// 		return result;
	// 
	// 	// Find the remaining number of bytes in the file.
	// 	size_t start = ftell(file);
	// 	fseek(file, 0, SEEK_END);
	// 	size_t size = ftell(file) - start;
	// 	// Reserve one extra byte because DataFile appends a '\n' to the end of each
	// 	// file it reads, and that's the most common use of this function.
	// 	result.reserve(size + 1);
	// 	result.resize(size);
	// 	fseek(file, start, SEEK_SET);
	// 
	// 	// Read the file data.
	// 	size_t bytes = fread(&result[0], 1, result.size(), file);
	// 	if(bytes != result.size())
	// 		throw runtime_error("Error reading file!");
	// 
	// 	return result;
	// }
	// 
	// 
	// 
	// void Files::Write(const string &path, const string &data)
	// {
	// 	File file(path, true);
	// 	Write(file, data);
	// }
	// 
	// 
	// 
	// void Files::Write(FILE *file, const string &data)
	// {
	// 	if(!file)
	// 		return;
	// 
	// 	fwrite(&data[0], 1, data.size(), file);
	// }
	// 
	// 
	// 
	// // Open this user's plugins directory in their native file explorer.
	// void Files::OpenUserPluginFolder()
	// {
	// 	OpenFolder(Config() + "plugins");
	// }
	// 
	// 
	// 
	// void Files::LogErrorToFile(const string &message)
	// {
	// 	if(!errorLog)
	// 	{
	// 		errorLog = File(config + "errors.txt", true);
	// 		if(!errorLog)
	// 		{
	// 			cerr << "Unable to create \"errors.txt\" " << (config.empty()
	// 				? "in current directory" : "in \"" + config + "\"") << endl;
	// 			return;
	// 		}
	// 	}
	// 
	// 	Write(errorLog, message);
	// 	fwrite("\n", 1, 1, errorLog);
	// 	fflush(errorLog);
	// }


}