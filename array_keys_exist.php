<?php

	/**
	 * Do deep check for existence of array key
	 * 
	 * @param array $keys   An array containing key values to look up $array in the order of nesting
	 * @param array $array Multi-dimensional array to search through each dimension by matching values in $keys
	 *
	 * array_keys_exist(array(12345,12,34),$multiDimensionalArray)
	 *
	 * Will return true if exists:
	 * $multiDimensionalArray[12345][12][34]
	 * 
	 * @return boolean    True if all the $keys are found otherwise false
	 */
	function array_keys_exist($keys,$array)
	{
		$a = $array ;
		foreach($keys as $key)
		{
			if(!array_key_exists($key,$a))
				return false ;
			$a = $a[$key] ;
		}
		return true ;
	}

?>
