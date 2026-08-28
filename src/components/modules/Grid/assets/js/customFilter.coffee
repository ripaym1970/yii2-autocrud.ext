CustomFilter = kendo.ui.Filter.extend({
  options: {
    name: 'CustomFilter',
  },

  init: (element, options)->
    options.fields = _.map options.fields, (field)->
      if !_.isEmpty(field.values)
        field.editorTemplate = (container, options)->
          $('<input data-bind="value: value" name="#{options.field}"/>').appendTo(
            container
          ).kendoDropDownList({
            dataTextField: 'text',
            dataValueField: 'value',
            dataSource: field.values
          })
        return field
      if field.type != 'date'
        return field
      #copypaste from kendo.filter.js, number template
      field.editorTemplate = field.editorTemplate || "<input id='#=id#' type='text' aria-label='#=field#' title='#=field#' data-#=ns#role='numerictextbox' data-#=ns#bind='value: value'/>"
      field.operators = {
        date: {
          currentDay: Yii.t("app", "Today"),
          currentWeek: Yii.t("app", "Since this Monday"),
          currentMonth: Yii.t("app", "This Month"),
          currentYear: Yii.t("app", "This Year"),

          previousMonth: Yii.t("app", "Previous Month"),

          ltEqDay: Yii.t("app", "After or equal (days) ago"),
          ltEqWeek: Yii.t("app", "After or equal (weeks) ago"),
          ltEqMonth: Yii.t("app", "After or equal (months) ago"),
          ltEqYear: Yii.t("app", "After or equal (years) ago"),

          gtEqDay: Yii.t("app", "Before or equal (days) ago"),
          gtEqWeek: Yii.t("app", "Before or equal (weeks) ago"),
          gtEqMonth: Yii.t("app", "Before or equal (months) ago"),
          gtEqYear: Yii.t("app", "Before or equal (years) ago"),

          isnull: Yii.t("app", "Is Null"),
          isnotnull: Yii.t("app", "Is Not Null"),
        }
      }
      return field
    kendo.ui.Filter.fn.init.call(this, element, options)

  applyFilter: ->
    kendo.ui.Filter.fn.applyFilter.call(this)
    @element.data("kendoWindow")?.close()

# monkey patch kendo.filter.js
  _showHideEditor: (container, model)->
    kendo.ui.Filter.fn._showHideEditor.call(@, container, model)
    if model.logic
      return
    operator = model.operator
    contains = _.contains [
      'currentDay', 'currentWeek', 'currentMonth', 'currentYear', 'previousMonth'
    ], operator
    if contains
      editorContainer = container.find(".k-filter-toolbar-item").eq(2)
      editorContainer.hide()
})
kendo.ui.plugin(CustomFilter)
