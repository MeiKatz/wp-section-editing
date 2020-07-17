jQuery(document).ready(function ($) {
  // constants
  var KEY_TAB = "Tab";

  var KEY_END = "End";
  var KEY_HOME = "Home";

  var KEY_ENTER = "Enter";
  var KEY_SPACE = "";

  var KEY_UP = "ArrowUp";
  var KEY_RIGHT = "ArrowRight";
  var KEY_DOWN = "ArrowDown";
  var KEY_LEFT = "ArrowLeft";

  var KEY_ASTERISK = "*";

  function isPrintableCharacter( str ) {
    return str.length === 1 && str.match( /\S/ );
  }

  function isTreeElement( element ) {
    var $el = $( element );
    return $el.is( "[role=tree]" );
  }

  function isTreeItemElement( element ) {
    var $el = $( element );
    return $el.is( "[role=treeitem]" );
  }

  function isTreeGroupElement( element ) {
    var $el = $( element );
    return $el.is( "[role=group]" );
  }

  function isPartOfTree( element, root ) {
    var $el = $( element );
    var $root = $( root );

    if ( !isTreeElement( $root ) ) {
      return false;
    }

    if ( isTreeElement( $el ) ) {
      return false;
    }

    var $parent = (
      $el
        .parents( "[role=tree]" )
        .first()
    );

    return $parent.is( $root );
  }

  function getTreeRootElement( element ) {
    var $el = $( element );
    var $root = $el;

    if ( $root.is( "[role=tree]" ) ) {
      return $root.get( 0 );
    }

    $root = $root.parents( "[role=tree]" ).first();

    if ( $root.length ) {
      return $root.get( 0 );
    }

    return null;
  }

  function getClosestRelevantParentElement( element ) {
    var $el = $( element );

    var $parents = (
      $el
        .parents( "[role=treeitem],[role=group]" )
    );

    if ( $parents.length ) {
      return $parents.get( 0 );
    }

    return null;
  }

  function getClosestRelevantChildElement( element ) {
    var $el = $( element );

    var $children = (
      $el
        .find( "[role=treeitem]" )
    );

    if ( $children.length ) {
      return $children.get( 0 );
    }

    return null;
  }

  function getClosestRelevantRelative( element ) {
    var $pivot = $( element );

    // walk the element tree upwards
    // and try to find the closest treeitem
    return (
      $pivot
        .parents()
        .toArray()
        // try to find the closest treeitem
        // to the pivot element
        .reduce(function ( result, el ) {
          if ( result ) {
            return result;
          }

          var $el = $( el );
          var $item = $el.find( "[role=treeitem]" );

          if ( $item.length ) {
            return $item.get( 0 );
          }

          return null;
        }, null)
    );
  }

  function toTreeItemWith( root ) {
    return function toTreeItem() {
      return new TreeItem(
        this,
        root
      );
    };
  }

  function isDirectSuccessorOf( sub ) {
    return function isDirectSuccessor() {
      return (
        $( this )
          .parents( "[role=treeitem],[role=tree]" )
          .first()
          .is( sub )
      );
    };
  }

  function Tree( element ) {
    this._$element = $( element );
  }

  Tree.fromElement = function fromElement( element ) {
    var root = getTreeRootElement( element );

    if ( !root ) {
      return null;
    }

    return new Tree( root );
  };

  Tree.prototype = {
    "getItem": function getItem( element ) {
      var $el = $( element );

      if ( !isPartOfTree( $el, this._$element ) ) {
        return null;
      }

      if ( isTreeItemElement( $el ) ) {
        return new TreeItem(
          $el,
          this._$element
        );
      }

      var $sup = getClosestRelevantParentElement( $el );

      if ( !$sup ) {
        return null;
      }

      if ( isTreeItemElement( $sup ) ) {
        return new TreeItem(
          $sup,
          this._$element
        );
      }

      var $sub = getClosestRelevantChildElement( $el );

      if ( $sub ) {
        return new TreeItem(
          $sub,
          this._$element
        );
      }

      var $rel = getClosestRelevantRelative( $el );

      if ( $rel ) {
        return new TreeItem(
          $rel,
          this._$element
        );
      }

      // element is no item
      return null;
    },

    "getItems": function getItems() {
      return (
        this._$element
          .find( "[role=treeitem]" )
          .map( toTreeItemWith( this._$element ) )
          .toArray()
      );
    },

    "getAccessableItems": function getAccessableItems() {
      var items = this.getItems();

      return items.filter(function ( item ) {
        return !item.isHidden();
      });
    },

    "getTopLevelItems": function getTopLevelItems() {
      return (
        this._$element
          .find( "[role=treeitem]" )
          .filter( isDirectSuccessorOf( this._$element ) )
          .map( toTreeItemWith( this._$element ))
          .toArray()
      );
    },
  };

  function TreeItem( element, root ) {
    this._$element = $( element );
    this._$root = $( root );
  }

  TreeItem.prototype = {
    "equals": function equals( other ) {
      if ( !( other instanceof TreeItem ) ) {
        return false;
      }

      var selfElement = this.getElement();
      var otherElement = other.getElement();

      return ( selfElement === otherElement );
    },

    "isLeaf": function isLeaf() {
      return !(
        this._$element
          .find( "[role=group]" )
          .length
      );
    },

    "isBranch": function isBranch() {
      return !this.isLeaf();
    },

    "isExpanded": function isExpanded() {
      if ( this.isLeaf() ) {
        return false;
      }

      var val = (
        this._$element
          .attr( "aria-expanded" )
      );

      return val !== "false";
    },

    "isCollapsed": function isCollapsed() {
      if ( this.isLeaf() ) {
        return false;
      }

      return !this.isExpanded();
    },

    "isHidden": function isHidden() {
      var current = this;

      while ( true ) {
        current = current.getParentItem();

        if ( !current ) {
          return false;
        }

        if ( current.isCollapsed() ) {
          return true;
        }
      }
    },

    "focus": function focus() {
      if ( this.isHidden() ) {
        return;
      }

      this._$element
        .prop( "tabindex", 0 );

      this._$root
        .find( "[role=treeitem]:not([tabindex=-1])" )
        .not( this._$element )
        .prop( "tabindex", -1 );

      this._$element
        .focus();
    },

    "collapse": function collapse() {
      if ( this.isCollapsed() ) {
        return;
      }

      this._$element
        .attr( "aria-expanded", "false" );

      this._$element
        .find( "[role=treeitem]" )
        .prop( "tabindex", -1 );
    },

    "expand": function expand() {
      if ( this.isExpanded() || this.isLeaf() ) {
        return;
      }

      this._$element
        .attr( "aria-expanded", "true" );
    },

    "getElement": function getElement() {
      return this._$element.get( 0 );
    },

    "getLabel": function getLabel() {
      var label = this._$element.attr("aria-label");

      // label is defined and not empty
      if ( label && label.trim().length ) {
        return label.trim();
      }

      var labelElementId = this._$element.attr("aria-labelledby");

      // label id is not defined or empty
      if ( !labelElementId || !labelElementId.trim().length ) {
        return this._$element.text().trim();
      }

      var $labelElement = $( "#" + labelElementId );

      if ( $labelElement.length ) {
        return $labelElement.text().trim();
      }

      return this._$element.text().trim();
    },

    "getParentItem": function getParentItem() {
      var $sup = (
        this._$element
          .parents( [
            "[role=tree]",
            "[role=treeitem]",
          ].join( "," ) )
          .first()
      );

      if ( !$sup.length ) {
        return null;
      }

      if ( isTreeElement( $sup ) ) {
        return null;
      }

      return new TreeItem(
        $sup.get( 0 ),
        this._$root
      );
    },

    "getChildren": function getChildren() {
      if ( this.isLeaf() ) {
        return [];
      }

      return (
        this._$element
          .find( "[role=treeitem]" )
          .filter( isDirectSuccessorOf( this._$element ) )
          .map( toTreeItemWith( this._$root ) )
          .toArray()
      );
    },

    "getSiblings": function getSiblings() {
      var parentItem = this.getParentItem();
      var $sub = $( parentItem && parentItem.getElement() );

      var $self = this._$element;
      var $root = this._$root;

      if ( !$sub.length ) {
        $sub = $root;
      }

      return (
        $sub
          .find( "[role=treeitem]" )
          .filter( isDirectSuccessorOf( $sub ) )
          .not( $self )
          .map( toTreeItemWith( $root ) )
          .toArray()
      );
    },
  };

  $("body").on("click", "[role=treeitem],[role=group]", function ( evt ) {
    var $target = $( evt.target );
    var tree = Tree.fromElement( evt.target );
    var currentItem = tree.getItem( evt.target );

    if ( $target.is( "button, input" ) ) {
      return;
    }

    // do not accept events
    // triggered by enter or space
    if ( evt.detail === 0 ) {
      return;
    }

    evt.preventDefault();
    evt.stopPropagation();
    currentItem.focus();

    // ignore events to
    if ( currentItem.isLeaf() ) {
      return;
    }

    if ( !currentItem.getChildren().length ) {
      return;
    }

    // toggle expansion state
    if ( currentItem.isExpanded() ) {
      currentItem.collapse();
    } else {
      currentItem.expand();
    }
  });

  $("body").on("keydown", "[role=treeitem]", function ( evt ) {
    var tree = Tree.fromElement( evt.target );
    var currentItem = tree.getItem( evt.target );

    switch (evt.key) {
      // Select the previous visible tree item.
      case KEY_UP:
        evt.preventDefault();
        evt.stopPropagation();

        var items = tree.getAccessableItems();
        var currentIndex = items.findIndex(function ( item ) {
          return currentItem.equals( item );
        });

        if ( currentIndex === 0 ) {
          break;
        }

        var prevItem = items[ currentIndex - 1 ];

        prevItem.focus();

        break;

      // Select next visible tree item.
      case KEY_DOWN:
        evt.preventDefault();
        evt.stopPropagation();

        var items = tree.getAccessableItems();
        var currentIndex = items.findIndex(function ( item ) {
          return currentItem.equals( item );
        });

        if ( currentIndex + 1 === items.length ) {
          break;
        }

        var nextItem = items[ currentIndex + 1 ];

        nextItem.focus();

        break;

      // Collapse the currently selected parent node
      // if it is expanded.
      // Move to the previous parent node (if possible)
      // when the current parent node is collapsed.
      case KEY_LEFT:
        evt.preventDefault();
        evt.stopPropagation();

        if ( currentItem.isExpanded() ) {
          currentItem.collapse();
          break;
        }

        var parentItem = currentItem.getParentItem();

        if ( parentItem ) {
          parentItem.focus();
          break;
        }

        break;

      // Expand the currently selected parent node
      // and move to the first child list item.
      case KEY_RIGHT:
        evt.preventDefault();
        evt.stopPropagation();

        if ( currentItem.isLeaf() ) {
          break;
        }

        if ( currentItem.isCollapsed() ) {
          currentItem.expand();
          break;
        }

        var children = currentItem.getChildren();

        if ( children.length ) {
          children[ 0 ].focus();
          break;
        }

        break;

      // Performs the default action (e.g. onclick event)
      // for the focused node.
      case KEY_SPACE:
      case KEY_ENTER:
        break;

      // Select the root parent node of the tree.
      case KEY_HOME:
        evt.preventDefault();
        evt.stopPropagation();

        var items = tree.getAccessableItems();

        items[ 0 ].focus();

        break;

      // Select the last visible node of the tree.
      case KEY_END:
        evt.preventDefault();
        evt.stopPropagation();

        var items = tree.getAccessableItems();
        var lastIndex = items.length - 1;

        items[ lastIndex ].focus();

        break;

      // Navigate away from tree.
      case KEY_TAB:
        break;

      // Expand all group nodes.
      case KEY_ASTERISK:
        evt.preventDefault();
        evt.stopPropagation();

        var siblings = currentItem.getSiblings();
        var shouldCollapse = !(
          siblings
            .filter(function ( item ) {
              return item.isCollapsed();
            })
            .length
        );

        siblings
          .forEach(function ( item ) {
            if ( shouldCollapse ) {
              // collapse all siblings
              // (extended keyboard support)
              item.collapse();
            } else {
              // expand all siblings
              // (recommended by WAI-ARIA)
              item.expand();
            }
          });

        break;

      default:
        // Focus moves to the next node
        // with a name that starts with
        // the typed character.
        if ( !isPrintableCharacter( evt.key ) ) {
          break;
        }

        evt.preventDefault();
        evt.stopPropagation();

        var firstChar = evt.key.toLowerCase();

        var items = tree.getAccessableItems();
        var labels = (
          items
            .map(function ( item, index ) {
              return {
                "label": item.getLabel().toLowerCase(),
                "index": index,
                "item": item,
              };
            })
            .filter(function ( item ) {
              return item.label[ 0 ] === firstChar;
            })
        );

        if ( !labels.length ) {
          break;
        }

        var currentIndex = (
          items
            .findIndex(function ( item ) {
               return currentItem.equals( item );
            })
        );

        // try to find item after the current item
        var nextItem = (
          labels
            .find(function ( item ) {
              return item.index > currentIndex;
            })
        );

        if ( !nextItem ) {
          // try to find item before the current item
          nextItem = (
            labels
              .find(function ( item ) {
                return item.index < currentIndex;
              })
          );
        }

        if ( nextItem ) {
          nextItem.item.focus();
          break;
        }

        break;
    }
  });
});
