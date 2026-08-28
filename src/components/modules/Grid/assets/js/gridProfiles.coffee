class Model extends Backbone.Model
  url: ->
    @get 'instanceUrl'

class application.Views.ProfileManager extends Marionette.View

  template: _.template ''

  ui: {
    selector: ".js-profile-selector",
  }

  events: {
    'change @ui.selector': 'onProfileChange',
  }

  initialize: (options)->
    @parent = options.parent
    @profiles = options.profiles
    @widget = options.widget
    @type = options.type
    @columns = options.columns
    # hack to not emit signal first time
    @firstTime = true

    @removeButtonId = _.uniqueId('b')
    @cloneButtonId = _.uniqueId('b')

    super

  toolbarWidget: ->
    @$el.data 'kendoToolBar'

  onRender: ->
    @$el.toggle @profiles.enabled
    return unless @profiles.enabled

    items = [
      {
        template: "<select class='js-profile-selector flex-fill mr-1'></select>",
      }
    ]

    controlButtons = [
      {
        type: 'button',
        icon: 'cog',
        click: _.bind(@onProfileConfigure, @),
        attributes: {
          title: Yii.t("app", "Configure layout"),
          class: 'mr-1'
        },
        overflow: 'never',
      },
      {
        type: 'button',
        icon: 'copy',
        id: @cloneButtonId,
        click: ()=>
          url = "#{homeUrl}grid/grid-profiles/clone?id=#{@selectedProfile.id}"
          $.post url, (model)=>
            @addModel model
        attributes: {
          title: Yii.t("app", "Clone layout"),
          class: 'mr-1'
        },
        overflow: 'never',
      },
      {
        type: 'button',
        icon: 'close',
        id: @removeButtonId,
        click: ()=>
          bootbox.confirm {
            message: Yii.t("app", "Are you sure you want to remove item ?"),
            title: Yii.t("app", "Are you sure ?"),
            callback: (result) =>
              return unless result
              $.ajax {
                url: "#{homeUrl}grid/grid-profiles/destroy?id=#{@selectedProfile.id}",
                type: "DELETE",
              }
              @profiles.availableProfiles = _.filter @profiles.availableProfiles, (i)=>
                i.id != @selectedProfile.id
              @profiles.currentProfileId = @profiles.availableProfiles[0].id
              @setupProfiles()
          }
        attributes: {
          title: Yii.t("app", "Remove layout"),
          class: 'mr-1'
        },
        overflow: 'never',
      }
    ]
    if @profiles.controllable
      items = items.concat controlButtons

    @$el.kendoToolBar({
      items: items
    })

    @bindUIElements()
    @ui.selector.parent().addClass 'flex-grow-1 mr-1'
    @ui.selector.parent().parent().addClass 'flex-grow-1'

    @selectedProfile = null
    @setupProfiles()

  setupProfiles: ->
    @ui.selector.html('')

    dataSource = {
      data: [],
      group: {
        field: 'group'
      }
    }
    for profile in @profiles.availableProfiles
      if profile.id == @profiles.currentProfileId
        @selectedProfile = profile
      hasFilter = !_.isEmpty profile.data_filter?.dataSource
      dataSource.data.push {
        id: profile.id,
        name: profile.name,
        hasFilter: hasFilter,
        group: profile.group
      }
    template = "#: name #
      #= !hasFilter
        ? ''
        : '<span class=\"badge badge-warning\">#{Yii.t('app', 'Has Filters')}</span>'
      #"
    @ui.selector.kendoDropDownList {
      autoWidth: false,
      dataSource: dataSource,
      value: @selectedProfile?.id,
      dataValueField: 'id',
      dataTextValue: 'name',
      template: template,
      valueTemplate: template,
    }
    @onProfileChange()

  onProfileChange: ->
    id = +@ui.selector.val()

    previousId = @selectedProfile.id

    @selectedProfile = _.find @profiles.availableProfiles, (i)-> i.id == id

    method = @selectedProfile?.editable && 'show' || 'hide'
    @toolbarWidget()[method] "##{@removeButtonId}"

    method = id && 'show' || 'hide'
    @toolbarWidget()[method] "##{@cloneButtonId}"

    # this will be copied by parent on profile change
    @profiles.currentProfileId = id

    #hack we don't need to fire selection change first time, when view was loaded
    if @firstTime
      @firstTime = false
      return

    $.post "#{homeUrl}grid/grid-profile-usage/track?id=#{id}&previousId=#{previousId}"
    @trigger 'profile:selected', @selectedProfile


  onProfileConfigure: ()->
    url = "#{homeUrl}grid/grid-profiles/edit/"

    model = new Model {}

    if @selectedProfile && @selectedProfile.id && @selectedProfile.editable
      url += "?id=" + @selectedProfile.id
      model.set 'id', @selectedProfile.id
    else
      url += "?url=#{@widget.dataSource.options.transport.read.url}"
      # sometimes url is an object
      model.set 'url', '' + @widget.dataSource.options.transport.read.url

    model.set 'instanceUrl', url

    view = new ProfileForm {
      currentFilter: @widget.dataSource.filter(),
      saveFilters: @widget.options.saveFilters,
      url: url,
      columns: @columns,
      type: @type,
      filterRowVisible: @parent.filterRowVisible,
      expandAllGroups: @parent.expandAllGroups,
      actions: ['save'],
      width: '50vw',
      title: Yii.t("app", "Layout profile management"),
    }

    @listenTo view, 'success', (data)->
      attributes = {
        name: data.name,
        notes: data.notes,
        saveFilters: data.saveFilters,
        auto_refresh_time: data.auto_refresh_time,
        has_custom_filter: data.has_custom_filter,
        type_id: if @type == 'grid' then 2 else 1
        shareIds: data.shareIds
        data: {
          dataSource: {},
          columns: data.columns,
          filterRowVisible: data.filterRowVisible,
          expandAllGroups: data.expandAllGroups,
        }
        data_filter: {
          dataSource: {},
        }
      }


      group = @widget.dataSource.group()
      if group
        attributes.data.dataSource.group = group

      sort = @widget.dataSource.sort()
      if sort
        attributes.data.dataSource.sort = sort

      if @type == 'grid'
        attributes.data.dataSource.pageSize = @widget.dataSource.pageSize()

      filter = @widget.dataSource.filter()
      if @widget.options.saveFilters && filter
        attributes.data_filter.dataSource.filter = filter

      model.save attributes, {
        wait: true,
        success: =>
          view.destroy()
          @addModel model.attributes
      }

  addModel: (model)->
    @profiles.availableProfiles = _.filter @profiles.availableProfiles, (i)->
      i.id != model.id
    @profiles.availableProfiles.push model
    @profiles.currentProfileId = model.id
    @setupProfiles()


class ProfileBehavior extends Marionette.Behavior

  ui: {
    fields: 'ul.js-fields'
    filterRowPlaceholder: '.js-filter-row-placeholder'
    expandAllGroupsPlaceholder: '.js-expand-all-groups-placeholder'
    checkAll: '.js-check-all'
  }

  events: {
    'change @ui.checkAll': 'onCheckAll',
  }

  filterRowTemplate: _.template '
    <div class="col-md-9 col-sm-10 offset-sm-2">
      <div class="custom-control custom-switch">
        <input type="hidden" name="field-filter-row-visible" value="0">
        <input type="checkbox" id="js-filter-row-visible" class="custom-control-input" name="field-filter-row-visible" value="1" <%= filterRowVisible ? "checked" : "" %> />
        <label class="custom-control-label" for="js-filter-row-visible">
          <%= Yii.t("app", "Show filter row") %>
        </label>
      </div>
    </div>
  '
  expandAllGroupsTemplate: _.template '
    <div class="col-md-9 col-sm-10 offset-sm-2">
      <div class="custom-control custom-switch">
        <input type="hidden" name="field-expand-all-groups" value="0">
        <input type="checkbox" id="js-expand-all-groups" class="custom-control-input" name="field-expand-all-groups" value="1" <%= expandAllGroups ? "checked" : "" %> />
        <label class="custom-control-label" for="js-expand-all-groups">
          <%= Yii.t("app", "Expand All Groups") %>
        </label>
      </div>
    </div>
  '

  columnTemplate: _.template '
    <% for(var i = 0; i < columns.length; i++) {
      var column=columns[i];
    %>
      <li data-title="<%= column.title %>">
        <input type="checkbox" class="js-check-all"/> <%= column.title %>
        <div class="js-subfields form-group subfields">
        <% for(var j = 0; j < column.columns.length; j++) {
          var subColumn = column.columns[j];
        %>
          <div class="form-group row px-4 py-1" data-field="<%=subColumn.field%>" data-width="<%=subColumn.width%>">
            <label class="control-label col-sm-3">
              <input type="checkbox" <%= ! subColumn.hidden ? "checked" : "" %>/>
              <%= subColumn.title %>
            </label>
            <div class="col-sm-9">
              <select multiple class="js-multiselect form-control">
                <option value="count" <%= _.indexOf(subColumn.aggregates || [], "count") != -1 ? "selected" : ""%> >
                  Count
                </option>
                <% if(subColumn.type == "number") { %>
                  <option value="average" <%= _.indexOf(subColumn.aggregates || [], "average") != -1 ? "selected" : ""%> >
                    Average
                  </option>
                  <option value="sum" <%= _.indexOf(subColumn.aggregates || [], "sum") != -1 ? "selected" : ""%> >
                    Sum
                  </option>
                  <option value="min" <%= _.indexOf(subColumn.aggregates || [], "min") != -1 ? "selected" : ""%> >
                    Min
                  </option>
                  <option value="max" <%= _.indexOf(subColumn.aggregates || [], "max") != -1 ? "selected" : ""%> >
                    Max
                  </option>
                <% } %>
              </select>
            </div>
          </div>
        <% } %>
        </ul>
      </li>
    <% } %>
    '


  onRender: ->
    @ui.fields.on "click", "input.js-check-all", (e)->
      e.stopPropagation()

    @ui.fields.append @columnTemplate {
      columns: @view.options.columns,
    }

    @ui.filterRowPlaceholder.append @filterRowTemplate {
      filterRowVisible: @view.options.filterRowVisible
    }

    if @view.type == 'grid'
      @ui.expandAllGroupsPlaceholder.append @expandAllGroupsTemplate {
        expandAllGroups: @view.options.expandAllGroups
      }
    else
      @ui.expandAllGroupsPlaceholder.hide()

    if @view.options.saveFilters
      $('#gridprofile-savefilters').removeAttr('disabled').prop('checked', 'checked');

    unless _.isEmpty(@view.options.currentFilter)
      $('#gridprofile-has_custom_filter').attr('disabled', 'disabled').attr('readonly', 'readonly')


    @ui.fields.kendoPanelBar()
    @$(".js-subfields").kendoSortable()
    @ui.fields.kendoSortable()
    @$(".js-multiselect").kendoMultiSelect()

  onCheckAll: (e)->
    target = $(e.target)
    target.closest('li').find('input[type="checkbox"]').prop 'checked', target.is(':checked')


class ProfileForm extends application.Views.Form

  behaviors: [
    ProfileBehavior
  ]

  initialize: (options)->
    @type = options.type
    super

  onFormSubmit: (e)->
    e.preventDefault()
    @trigger("success", @serialize())

  serialize: ->
    shareIds = []
    @$("[name='shareIds[]']").each ->
      shareIds.push $(@).val()

    result = {
      name: @$("#gridprofile-name").val(),
      notes: @$("#gridprofile-notes").val(),
      auto_refresh_time: @$("#gridprofile-auto_refresh_time").val(),
      filterRowVisible: @$("#js-filter-row-visible").is(':checked'),
      expandAllGroups: @$("#js-expand-all-groups").is(':checked'),
      shareIds: shareIds,
      columns: [],
      saveFilters: @$("#gridprofile-savefilters").is(':checked')
      has_custom_filter: @$("#gridprofile-has_custom_filter").is(':checked')
    }

    for section in @$("ul.js-fields > li")
      $section = $(section)
      column = {
        title: $section.data('title'),
        columns: []
      }
      for field in $("div.js-subfields > div", $section)
        $field = $(field)
        element = {
          field: $field.data('field'),
          aggregates: $("select.js-multiselect", $field).val()
          hidden: !$("input:checkbox", $field).is(':checked')
        }

        width = $field.data 'width'
        if width
          element.width = +width
        column.columns.push element
      result.columns.push column

    result
