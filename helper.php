<?php defined('_JEXEC') or die;

/**
 * File       helper.php
 * Created    8/6/13 3:41 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/Menu-Wrench/issues
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

class modMenuwrenchHelper {

	/**
	 * Constructor
	 *
	 * @param JRegistry $params: module parameters
	 * @since 0.1
	 *
	 */
	public function __construct($params) {
		$this->app    = JFactory::getApplication();
		$this->menu   = $this->app->getMenu();
		$this->active = $this->menu->getActive();
		$this->params = $params;
	}

	/**
	 * Retrieves all menu items, sorts, combines, mixes, stirs, and purges what we want in a logical order
	 *
	 * @return mixed
	 * @since 0.1
	 *
	 */
	function getBranches() {
		$parentItems = $this->params->get('parentItems');
		$items       = $this->menu->_items;

		// Convert parentItems to an array if only one item is selected
		if (!is_array($parentItems)) {
			$parentItems = str_split($parentItems, strlen($parentItems));
		}

		/**
		 * Builds menu hierarchy by nesting children in parent object's 'children' property
		 */
		foreach ($items as $item) {
			if ($item->parent != 0) {
				// Reset array counter to last tree item, which is self
				end($item->tree);
				// Set $previous to next to last tree item value
				$previous = prev($item->tree);
				// If $previous is not self, it's a parent
				if ($previous != $item->id) {
					$items[$previous]->children[$item->id] = $item;
				}
			}
		}

		foreach ($items as $key => $item) {

			// Remove non-selected menu item objects
			if (!in_array($key, $parentItems)) {
				unset($items[$key]);
			}

			/**
			 * Builds object classes
			 */
			if (isset($item->id)) {
				$item->class = 'item' . $item->id . ' ' . $item->alias;
			}

			// Add parent class to all parents
			if (isset($item->children)) {
				$item->class .= ' parent';
			}

			// Add current class to specific item
			if (isset($this->active->id, $item->id)) {
				if ($item->id == $this->active->id) {
					$item->class .= ' current';
				}
			}

			// Add active class to all items in active branch
			if (isset($this->active->tree, $item->id)) {
				if (in_array($item->id, $this->active->tree)) {
					$item->class .= ' active';
				}
			}
		}

		$this->countChildren($items);

		return $items;
	}

	/**
	 * Recursively count children for later splitting
	 *
	 * @param $items
	 * @return mixed
	 */

	private function countChildren($items) {

		foreach ($items as $item) {
			if (isset($item->children)) {
				$item->childrentotal = count($item->children);
				foreach ($item->children as $item) {
					if (isset($item->children)) {
						$item->childrentotal = count($item->children);
						$this->countChildren($item);
					}
				}
			} else {
				return $items;
			}
		}
	}

	/**
	 * Renders the menu
	 *
	 * @param $item                 : the menu item
	 * @param string $containerTag  : optional, declare a different container HTML element
	 * @param string $containerClass: optional, declare a different container class
	 * @param string $itemTag       : optional, declare a different menu item HTML element
	 * @param int $level            : counter for level of depth that is rendering.
	 * @return string
	 *
	 * @since 0.1
	 */

	public function render($item, $containerTag = '<ul>', $containerClass = 'menu', $itemTag = '<li>', $level = 0) {

		$itemOpenTag       = str_replace('>', ' class="' . $item->class . '">', $itemTag);
		$itemCloseTag      = str_replace('<', '</', $itemTag);
		$containerOpenTag  = str_replace('>', ' class="' . $containerClass . '">', $containerTag);
		$containerCloseTag = str_replace('<', '</', $containerTag);
		$depth             = htmlspecialchars($this->params->get('depth'));
		$columns           = htmlspecialchars($this->params->get('columns'));

		if ($item->type == 'separator') {
			$output = $itemOpenTag . '<span class="separator">' . $item->name . '</span>';
		} else {
			$output = $itemOpenTag . '<a href="' . JRoute::_($item->link . '&Itemid=' . $item->id) . '"/>' . $item->name . '</a>';
		}

		$level++;

		if (isset($item->children) && $level <= $depth) {

			$output .= $containerOpenTag;

			if ($columns > 0 && isset($item->childrentotal)) {
				// Calculate divisor based on this item's total children and parameter
				$divisor = ceil($item->childrentotal / $columns);
			}

			// Zero counter for calculating column split
			$index = 0;

			foreach ($item->children as $item) {

				if ($columns > 0) {
					if ($index > 0 && fmod($index, $divisor) == 0) {
						$output .= $containerCloseTag . $containerOpenTag;
					}
				}

				$output .= $this->render($item, $containerTag, $containerClass, $itemTag, $level);

				// Increment, rinse, repeat.
				$index++;
			}
			$output .= $itemCloseTag;
			$output .= $containerCloseTag;
		}

		$output .= $itemCloseTag;

		return $output;
	}
}
