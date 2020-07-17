jQuery(document).ready(function ($) {
  // constants
  var KEY_TAB = "Tab";

  var KEY_HOME = "Home";
  var KEY_END = "End";

  var KEY_RIGHT = "ArrowRight";
  var KEY_LEFT = "ArrowLeft";

  function getTabs($tab) {
    return (
      $tab
        .parents("[role=tablist]")
        .first()
        .find("[role=tab]")
    );
  }

  function setActiveTab($tab) {
    var $tabs = getTabs($tab);
    var $panels = getTabPanelsByTab($tab);
    var $panel = getTabPanelByTab($tab);

    $panels.not($panel)
      .prop("hidden", true);

    $panel
      .prop("hidden", false);

    $tabs.not($tab)
      .attr("aria-selected", "false")
      .prop("tabindex", -1);

    $tab
      .attr("aria-selected", "true")
      .prop("tabindex", 0);

    $tab.focus();
  }

  function getActiveTab($tab) {
    var $tabs = getTabs($tab);
    return $tabs.has("[aria-selected=true]").first();
  }

  function getTabByTabPanel($panel) {
    var panelId = $panel.prop("id");
    var $tab = $("[role=tab][aria-controls=" + panelId + "]").first();

    if ($tab.length) {
      return $tab;
    } else {
      return null;
    }
  }

  function getTabPanelByTab($tab) {
    var panelId = $tab.attr("aria-controls");
    var $panel = $("[role=tabpanel]#" + panelId);

    if ($panel.length) {
      return $panel;
    } else {
      return null;
    }
  }

  function getTabPanelsByTab($tab) {
    var $tabs = getTabs($tab);

    // collect all possible selectors for related
    // tab-panels
    var selectors = $tabs.map(function (i, el) {
      var panelId = $(el).attr("aria-controls");
      return "[role=tabpanel]#" + panelId;
    }).toArray();

    return $(selectors.join(", "));
  }

  $("body").on("click", "[role=tab]", function (e) {
    var $tab = $(e.currentTarget);
    var $panel = getTabPanelByTab($tab);

    if ($panel) {
      e.preventDefault();
      setActiveTab($tab);
    }
  });

  $("body").on("keydown", "[role=tab]", function (e) {
    var $tab = $(e.currentTarget);
    var $tabs = getTabs($tab);

    switch ( e.key ) {
      // set focus to first tab in the list
      case KEY_HOME:
        e.preventDefault();
        setActiveTab($tabs.eq(0));
        break;

      // set focus to last tab in the list
      case KEY_END:
        e.preventDefault();
        setActiveTab($tabs.eq(-1));
        break;

      // set focus to previous tab in the list;
      // if current tab is the first tab in the list,
      // set focus to the last tab in the list
      case KEY_LEFT:
        e.preventDefault();
        var index = $tabs.index($tab);
        var length = $tabs.length;
        var nextIndex = (index + length - 1) % length;

        setActiveTab($tabs.eq( nextIndex ));
        break;

      // set focus to next tab in the list;
      // if current tab is the last tab in the list,
      // set focus to the first tab in the list
      case KEY_RIGHT:
        e.preventDefault();
        var index = $tabs.index($tab);
        var length = $tabs.length;
        var nextIndex = (index + length + 1) % length;

        setActiveTab($tabs.eq( nextIndex ));
        break;

      // set focus to current tab panel
      case KEY_TAB:
        if (e.shiftKey) {
          break;
        }

        $panel = getTabPanelByTab($tab);

        if (!$panel) {
          break;
        }

        e.preventDefault();
        $panel.focus();
        break;
    }
  });

  $("body").on("keydown", "[role=tabpanel]", function (e) {
    if (e.key != KEY_TAB || !e.shiftKey) {
      return;
    }

    var $panel = $(e.currentTarget);
    var $tab = getTabByTabPanel($panel);

    if ($tab) {
      e.preventDefault();
      setActiveTab($tab);
    }
  });
});
