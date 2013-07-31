bx-structure
============

Bitrix widgets structure component, for page-level inheritable widgets layout.

## What is Structure

Structure is path-related meta config for anything. In this context we're configuring "widgets" showing & ordering depending of current url path (URL query part is ignored).

Any page contains (usually templates) `InsetManager::widget()` calls, marking up slots  (places for widgets rendering).

For example:

~~~yaml
# defining default slots with arrays
sidebar-1: []
sidebar-2: []
title-bar: [title]
/:
	# here's is default site root config
	sidebar-1: widget-name-without-php-ext
	# widget can be array
	sidebar-2: [another-widget, {config-option: foo, anther-option: [1,2,3]]}]
	title-bar: # empty string disables widget slot
	# now we redefining some widgets placement at /pagename
	/pagename:
		sidebar-1: "another widget name"
		# hide sidebar-2
		sidebar-2:
		title-bar: title #let's show title

	# any other next-level page will take following options
	/*:
		sidebar-1:
		sidebar-2:
		# we have no widgets!
~~~

This config will be cached till next file change.
