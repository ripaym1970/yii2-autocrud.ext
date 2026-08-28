class AbstractGridTree extends Marionette.View

  template: _.noop

  ui: {
    toolbar: '.js-toolbar',
    checkbox: 'input:checkbox',
  }

  regions: {
    profileManagement: {
      el: '.js-profiles',
      replaceElement: true,
    }
  }

  events: {
    'keypress': 'onKeypress',
    'reload': 'onReload',
    'change @ui.checkbox': 'onCheckboxChange',
  }

  onKeypress: (e)->
    if e.ctrlKey == false && e.keyCode == 13
      e.preventDefault()

  childViewEvents: {
    'profile:selected': 'onProfileSelected'
  }

  menuButtons: ->
    [
      {
        icon: 'filter',
        text: Yii.t("app", "Toggle filter row"),
        hidden: @hasCustomFilter
        click: ()=>
          @toggleRowFilter !@filterRowVisible
      },
      {
        icon: 'arrows-dimensions',
        text: Yii.t("app", "Toggle resizable mode"),
        click: ()=>
          if @$el.resizable('instance')
            @$el.resizable('destroy')
          else
            @$el.resizable {
              resize: =>
                @widget().resize()
            }
      },
      {
        icon: 'file-excel',
        text: Yii.t("app", "Export to .xls sheet"),
        click: ()=>
          @widget().saveAsExcel()
      },
      {
        icon: 'file-pdf',
        text: Yii.t("app", "Export to .pdf"),
        click: ()=>
          @widget().saveAsPDF()
      },
    ]

  initialize: ->
    super
    @filterRowVisible = true
    @autoRefreshTime = 0
    @hasCustomFilter = false
    @customFilter = $("<div></div>")
    @selectionChannel = Backbone.Radio.channel("selection")
    @widgetChannel = Backbone.Radio.channel("widget")

    @rendered = false

    @config = @defaultConfig()
    if @config.toolbar not in [false, null, undefined]
      @config.toolbar += '
        <div class="mr-1 flex-fill"></div>
        <div class="js-toolbar mx-0 px-0"></div>
        <div class="js-profiles mx-0 px-0"></div>
        '
    @profiles = @$el.data('profiles')

    # by default grid will not be rendered
    # because it's pretty heavy component
    # splitter code also will be started only when tab is active
    # and it will trigger 'resize' after rendered, so we rely on this
    splitter = @$el.closest('.k-splitter')
    splitterReady = @$el.closest('.k-pane').length > 0
    if splitter.length && !splitterReady
      splitter.one 'resize', =>
# k.pane appears only when splitter is ready
        @$el.closest('.k-pane').addClass 'd-flex flex-column'
        tabsAutoStart(@$el, _.bind(@start, @))
    else
      tabsAutoStart(@$el, _.bind(@start, @))


  start: ->
    if @profiles.enabled
      selectedProfile = _.find @profiles.availableProfiles, (i) =>
        i.id == @profiles.currentProfileId
      @onProfileSelected selectedProfile
      return
    @render()

  reload: ->
    @widget().dataSource.read()
    if @timer && !@timer.isPaused()
      @timer.reset()

  toggleRowFilter: (value)->
    @$("tr.k-filter-row").toggle value
    @filterRowVisible = value
    @widget().refresh()

  onReload: (e)->
    @reload()
    e.stopImmediatePropagation()

  onSelect: (e)->
    selectedRows = @widget().select()
    selectedItems = []
    for i in selectedRows
      selectedItems.push @widget().dataItem i
    selectedItems = selectedItems.length == 1 && selectedItems[0] || selectedItems
    @selectionChannel.trigger "model:select", selectedItems, @

  onCheckboxChange: (e)->
    name = e.target.name
    return unless name
    dataItem = @widget().dataItem($(e.target).closest("tr"))
    dataItem.set name, e.target.checked

  onProfileSelected: (profile)->
    @hasCustomFilter = profile.has_custom_filter
    if profile.has_custom_filter
      @filterRowVisible = false
    else
      @filterRowVisible = _.isUndefined(profile.data.filterRowVisible) || profile.data.filterRowVisible || false

    @timer?.pause()

    @autoRefreshTime = profile.auto_refresh_time || 0
    if @autoRefreshTime > 0
      @timer = new easytimer.Timer()
      @timer.start({
        startValues: {
          seconds: @autoRefreshTime
        },
        countdown: true,
      })
      @timer.addEventListener 'secondsUpdated', (e) ->
        time = e.detail.timer.getTimeValues().toString(['minutes', 'seconds'])
        $(".js-auto-refresh-timer").text(time)
      @timer.addEventListener 'stopped', _.bind(@reload, @)

    @cleanUp()

    defaultConfig = @defaultConfig()
    @config.dataSource = defaultConfig.dataSource
    if profile.has_custom_filter
      @config.filterable = false
    else
      @config.filterable = defaultConfig.filterable

    @config.dataSource['requestStart'] = _.once =>
      progressOn @$el
    @config.dataSource['requestEnd'] = _.once =>
      progressOff @$el
    if _.isEmpty profile.data
# default state
      @config.columns = defaultConfig.columns
      @render()
      return

    realColumns = @getCurrentColumns()
    realColumns.forEach (item, i, collection)->
      item.priority = collection.length + i
      item.columns.forEach (item, i, collection)->
        item.priority = collection.length + i

    profile.data.columns.forEach (item, i)->
      item.priority = i + 1
      item.columns.forEach (item, i)->
        item.priority = i + 1

    aggregates = []
    realColumns = _.sortBy realColumns, (realColumn)->
      master = _.find profile.data.columns, (x)->
        x.title == realColumn.title

      return realColumn.priority unless master


      # sort subcolumns
      realColumn.columns = _.sortBy realColumn.columns, (realSubColumn)->
        masterSubColumn = _.find master.columns, (item)->
          item.field == realSubColumn.field
        return realSubColumn.priority unless masterSubColumn
        realSubColumn.aggregates = []
        realSubColumn = _.extend realSubColumn, masterSubColumn

        realSubColumn.groupFooterTemplate = []
        realSubColumn.footerTemplate = []

        for aggregate in realSubColumn.aggregates || []
          template = "#{aggregate.charAt(0).toUpperCase()}#{aggregate.slice(1)}: #= kendo.toString(#{aggregate}, 'n') #"
          realSubColumn.groupFooterTemplate.push template
          realSubColumn.footerTemplate.push template

          aggregates.push {
            aggregate: aggregate,
            field: realSubColumn.field,
          }

        realSubColumn.groupHeaderColumnTemplate = realSubColumn.groupFooterTemplate.join '<br/>'
        realSubColumn.groupFooterTemplate = realSubColumn.groupFooterTemplate.join '<br/>'
        realSubColumn.footerTemplate = realSubColumn.footerTemplate.join '<br/>'

        masterSubColumn.priority

      master.priority

    @config.columns = realColumns
    @config.dataSource.aggregate = aggregates

    if profile.data.dataSource.pageSize
      @config.dataSource.pageSize = profile.data.dataSource.pageSize

    if profile.data.dataSource.group
      @config.dataSource.group = profile.data.dataSource.group
      for i in @config.dataSource.group
        i.aggregates = aggregates

    @config.dataSource.sort = profile.data.dataSource.sort

    @config.dataSource.filter = []
    if @config.saveFilters && profile.data_filter?.dataSource?.filter
      @config.dataSource.filter = _.clone profile.data_filter.dataSource.filter

    @render()

  setHeight: ->
    if @config.height
      return
    height = @$el.innerHeight()
    if height > 2
      @config.height = height - 2

  setFilter: ->
    return unless @hasCustomFilter

    fields = []
    for group in @config.columns
      columnHeader = group.title || ''
      for field in group.columns || [group]
        if !field.filterable
          continue
        title = field.title
        if columnHeader
          title = "#{columnHeader}: #{title}"
        values = []
        if field.type == 'number' || field.type == 'string'
          values = field.values || []
        fields.push {
          name: field.field,
          type: field.type,
          label: title,
          values: values,
        }

    widget = @widget()
    @customFilter.kendoCustomFilter({
      dataSource: widget.dataSource,
      fields: fields,
      applyButton: true,
      expressionPreview: true,
      expression: widget.dataSource.filter(),
    }).kendoWindow({
      visible: false,
      modal: true,
      title: Yii.t("app", "Filter Configuration"),
      open: (e)->
        e.sender.visible = true
      close: (e)=>
        e.sender.visible = false
        @toggleFilterToolbarColor()
    }).data('kendoWindow').center()
    @toggleFilterToolbarColor()

  toggleFilterToolbarColor: ->
    @$(".js-filter-toolbar-button").toggleClass 'btn-warning', !_.isEmpty(@widget().dataSource.filter())

  setToolbar: ->
    @ui.toolbar.kendoToolBar({
      items: [
        {
          type: 'button',
          icon: 'reload',
          text: if @autoRefreshTime then '<span class="js-auto-refresh-timer"></span>' else '',
          click: _.bind(@reload, @)
          attributes: {
            title: Yii.t("app", "Reload"),
            class: 'mr-1'
          },
          hidden: !(@config.dataSource.transport?.read && true || false)
          overflow: 'never'
        },
        {
          type: 'button',
          icon: 'filter',
          overflow: 'never'
          attributes: {
            title: Yii.t("app", "Filter Settings"),
            class: "mr-1 js-filter-toolbar-button",
          },
          hidden: !@hasCustomFilter
          click: =>
            window = @customFilter.data('kendoWindow')
            window.visible && window.close() || window.open()
        },
        {
          type: 'button',
          icon: 'arrows-resizing',
          click: _.bind(@fitColumns, @),
          attributes: {
            title: Yii.t("app", "Fit columns"),
            class: "mr-1"
          },
          overflow: 'never'
        },
        {
          type: 'button',
          icon: 'fullscreen',
          click: (e)=>
            @$el.toggleClass 'grid-fullscreen'
            $(e.target).find('span')
              .toggleClass('k-i-full-screen-exit')
              .toggleClass('k-i-fullscreen')
            @widget().refresh()
          attributes: {
            title: Yii.t("app", "Toggle full screen"),
            class: 'mr-1'
          },
          overflow: 'never'
        },
        {
          type: "splitButton",
          text: '',
          icon: 'menu',
          menuButtons: @menuButtons(),
          overflow: 'never',
        }
      ]
    })

  startTooltips: ->
    @$el.kendoTooltip {
      filter: 'td[role="gridcell"]:not(.no-tooltip):not(:empty), th.k-header span.k-column-title',
      content: (e)->
        $target = $(e.target)
        $target.data("title") || $target.text()
    }

  addDecorations: ->
    @$(".k-grid-toolbar, .k-grouping-header, .k-pager-wrap")
      .addClass "bg-primary-700 bg-success-gradient"

    # adding bg-primary-500 to filter button breaks its logic
    for i in @$(".k-header, .k-toolbar, .k-button, .k-select, .k-filter-row th, .k-grid-header-wrap, .k-grid-header")
      $i = $(i)
      $i.attr('class', "bg-primary-500 " + $i.attr("class"))

    @$(".k-grid-pager .k-widget.k-dropdown").addClass 'w-100'
    @$(".k-grid-toolbar .k-toolbar").css {
      'border': 'none',
      'background': 'none',
      'box-shadow': 'none',
    }

  setDraggable: ->
    return unless @config.draggable
    @widget().table.kendoDraggable({
      filter: 'tr',
      hint: (element)=>
        e = element.closest('tr').clone()
          .css({
          'opacity': 0.6,
        })
        # replace ids with random
        for i in $("td", e)
          i.id = _.uniqueId("i-drag-")
        return e.wrap('<table class="badge badge-success"></table>').closest('table')
    })

  setDroppable: ->
    return unless @config.droppable
    @widget().table.kendoDropTargetArea {
      filter: 'tr',
      dragenter: (e)->
        e.dropTarget.toggleClass 'bg-info'
      dragleave: (e)->
        e.dropTarget.toggleClass 'bg-info'
      drop: (e)=>
        e.dropTarget.toggleClass 'bg-info'
        sourceControl = e.draggable.currentTarget.closest('.k-widget')
        sourceControl = sourceControl.getKendoGrid() || sourceControl.getKendoTreeList()
        sourceRecord = sourceControl.dataSource.getByUid e.draggable.currentTarget.data('uid')
        targetRecord = @widget().dataSource.getByUid e.dropTarget.data('uid')
        @widgetChannel.trigger 'drop', @, sourceRecord, targetRecord
    }

  hideMultiColumn: ->
    if @config.hideMultiColumn && @widget().columns.length == 1
      $("tr:first", @widget().thead).hide()

  onRender: ->
    @bindUIElements()
    @setToolbar()
    @setFilter()

    if @profiles.enabled && @profiles.controllable && @config.toolbar
      @showChildView 'profileManagement', new application.Views.ProfileManager {
        parent: @,
        widget: @widget(),
        profiles: @profiles,
        type: @widgetType(),
        columns: @getCurrentColumns(),
      }

    @startTooltips()
    @addDecorations()

    @setDraggable()
    @setDroppable()

    @rendered = true

    @widgetChannel.trigger 'render', @

    @toggleRowFilter @filterRowVisible

  cleanUp: ->
    return unless @rendered
    #store profiles copy (it could change)
    child = @getChildView('profileManagement')
    if child
      @profiles = child.profiles

    @detachChildView 'profileManagement'
    @$el.data('kendoTooltip').destroy()

    if @$el.is(".ui-resizable")
      @$el.resizable 'destroy'

    @customFilter.data('kendoWindow')?.destroy()
    kendo.destroy @customFilter
    @customFilter.html('')

    kendo.destroy @$el
    @$el.empty()

  onBeforeDestroy: ->
    @cleanUp()
    @widgetChannel.trigger 'destroy', @


  defaultConfig: ->
# standard clone is not working...
# config = JSON.parse(JSON.stringify(@$el.data("params")))
    config = $.extend true, {}, @$el.data("params")

    # hack to pass correct values to server
    config.dataSource.transport?.parameterMap = (data, type)->
      if type == 'read' && _.isObject data.filter
        traverser = (collection)->
          for i in collection
            if i.value instanceof Date
# kendo bug
# https://www.telerik.com/forums/issue-with-timezone-on-date-column-filter
              i.value = kendo.toString i.value, 'yyyy-MM-dd'
            traverser(i.filters || [])
        traverser(data.filter.filters || [])
      data

    config.change = _.bind @onSelect, @
    config.dataBound = _.bind(@dataBound, @)
    config.dataBinding = _.bind(@dataBinding, @)

    config.excelExport = (e) =>
      rows = e.workbook.sheets[0].rows
      for ri in [0...rows.length]
        row = rows[ri]

        for ci in [0...row.cells.length]
          cell = row.cells[ci]
          if !cell.value || typeof cell.value isnt 'string'
            continue
          cell.value = cell.value.replace(/<(?:.|\n)*?>/gm, '')

    config

  colorizeFields: (columns) ->
    widget = @widget()
    for column in columns
      if column.columns
        @colorizeFields(column.columns)
        continue

      colorAttributes = column['color-attributes']
      if !colorAttributes
        continue

      colorTarget = colorAttributes['target']
      colorColumnIndex = widget.thead.find("[data-field=#{column.field}]").index()

      fields = colorAttributes['for'].split(',')
      for field in fields
        columnIndex = widget.thead.find("[data-field=#{field}]").index()
        if columnIndex == -1
          continue
        for row in widget.tbody.children(":not(.k-grouping-row)")
          cells = row.children
          color = cells[colorColumnIndex].textContent
          cell = cells[columnIndex]
          if colorTarget == 'background'
            cell.style['background-color'] = color
          else if colorTarget == 'foreground'
            cell.style.color = color

  dataBound: ->
    @colorizeFields(@widget().options.columns)
    @selectionChannel.trigger "reload", @
    # after major kendo upgrade it doesn't fire "change" event in case with persistSelection
    if @widget().select().length
      @onSelect(null)

  widget: ->
# implement me in children

  getCurrentColumns: ->
    @widget()?.columns || @config.columns

  selectById: (id)->
    widget = @widget()
    model = widget.dataSource.get(id)
    if model
      item = widget.itemFor model
      widget.select item

  dataBinding: ->
# reimplemented in grid

class application.Views.Grid extends AbstractGridTree

  widgetType: ->
    "grid"

  widget: ->
    @$el.getKendoGrid()

  initialize: ->
    @expandAllGroups = true
    super

  fitColumns: ->
    w = @widget()
    w.autoFitColumns()

  menuButtons: ->
    callback = (callable)=>
    [
      {
        icon: 'expand',
        text: Yii.t("app", "Expand All Groups"),
        click: =>
          w = @widget()
          for i in w.tbody.find('tr.k-grouping-row')
            $i = $(i)
            if $i.find('a.k-i-expand')
              w.expandGroup $i
      },
      {
        icon: 'collapse',
        text: Yii.t("app", "Collapse All Groups"),
        click: =>
          w = @widget()
          for i in w.tbody.find('tr.k-grouping-row')
            $i = $(i)
            if $i.find('a.k-i-expand')
              w.collapseGroup $i
      },
    ].concat super

  onRender: ->
    @setHeight()
    @$el.kendoGrid @config
    @hideMultiColumn()
    @expandedGroups = {}
    @scrollTop = 0
    @setupGroups()
    # 64383, grid inside PANE not always reacts on resize
    @$el.closest('.k-splitter').on 'resize', (e)=>
      @widget().refresh()
    super

  onProfileSelected: (profile)->
    @expandAllGroups = _.isUndefined(profile.data.expandAllGroups) || profile.data.expandAllGroups || false
    super

  rowGroupKey: (row)->
    widget = @widget()
    next = row.nextUntil("[data-uid]").next()
    item = widget.dataItem(next.length && next || row.next())
    groupIdx = row.children(".k-group-cell").length
    field = widget.dataSource.group()[groupIdx].field
    groupValue = item[field]
    return "" + groupIdx + groupValue

  setupGroups: ->
    @widget().table.on 'click', '.k-grouping-row .k-i-collapse, .k-grouping-row .k-i-expand', (e)=>
      $target = $(e.target)
      groupKey = @rowGroupKey $target.closest('tr')
      @expandedGroups[groupKey] = $target.hasClass 'k-i-collapse'

  dataBinding: ->
# content is not always available
    @scrollTop = @widget().content?[0].scrollTop || 0
    super

  dataBound: ->
    super
    w = @widget()
    groups = w.dataSource.group()
    return if !groups.length || @expandAllGroups

    for row in w.tbody.children(".k-grouping-row")
      $row = $(row)
      groupKey = @rowGroupKey $row
      if !@expandedGroups[groupKey]
        w.collapseGroup $row

    # without grouping grid restores scroll position by itself, but
    # since we probably collapsed something we have to restore scrolling
    if @scrollTop
      w.content?[0].scrollTop = @scrollTop

class application.Views.Tree extends AbstractGridTree

  widgetType: ->
    "tree"

  widget: ->
    @$el.getKendoTreeList()

  initialize: ->
    super
    @selectedId = null

  fitColumns: ->
    w = @widget()
    for column, index in w.columns
      w.autoFitColumn index

  setExpandable: ->
    done = false
    for i in @config.columns
      for j in i.columns
        if j.hidden || done
          j.expandable = false
          continue
        j.expandable = true
        done = true

  onRender: ->
    @setExpandable()
    @setHeight()
    @$el.kendoTreeList @config
    @widget().bind "expand", (e)=>
      @selectedId = e.model.id
    @hideMultiColumn()
    super

  dataBound: ()->
    super
    @selectById @selectedId
