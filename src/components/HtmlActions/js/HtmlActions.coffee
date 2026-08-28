class AbstractForm extends Marionette.View
  ui: {
    'twitterTabs': '.nav-tabs li a',
    'errortab': '.tab-pane:has(.invalid-feedback:not(:empty)):first',

    'form': 'form:first',
    'reloadable': '.js-reload',
    'reloadableLink': 'a.js-reload',
  }

  events: {
    'click .nav-tabs li a': 'twitterTabShow',
    'submit @ui.form': 'onFormSubmit',
    'change @ui.form': 'onFormChange',
    'change @ui.reloadable': 'onReloadableChange',
    'click @ui.reloadableLink': 'onReloadableChange',
    'reload': 'onReload'
  }

  initialize: (options)->
    super
    @id = options.id || null
    @templateData = options.templateData
    @listenTo @, 'failure', @onFailure
    @savingData = false

    if @options.url
      @loadContent()
    if @templateData
      @render()

  loadContent: ->
    return unless @options.url
    progressOn $("body")
    $.get @options.url, (data)=>
      progressOff $("body")
      return unless data.length
      @templateData = data
      @render()

  onFailure: ->
    @render()

  finish: ->
    @trigger 'success', @id
    channel = Backbone.Radio.channel("widget")
    channel.trigger "success", @, @id
    @destroy()

  cleanUp: ->
    @ui.form.yiiActiveForm?('destroy')
    kendo.destroy @$el

  onBeforeDestroy: ->
    @cleanUp()

  onReload: (e)->
    e.preventDefault()
    e.stopImmediatePropagation()
    if @ui.form.length
      @onReloadableChange()
    if @options.url
      @loadContent()

  onFormSubmit: (e)->
    e.preventDefault()
    e.stopImmediatePropagation()
    if @savingData
      return

    @savingData = true
    progressOn(@$el)
    # stop codemirror if present
    for i in @$(".CodeMirror")
      i.CodeMirror.toTextArea()


    @ui.form.ajaxSubmit {
      forceSync: true,
      error: =>
        progressOff(@$el)
        @savingData = false
      success: (data, status, xhr)=>
        progressOff(@$el)
        @id = xhr.getResponseHeader("X-Model-ID") || @id
        @savingData = false
        if !data || !data.length
          @finish()
          return
        @templateData = data
        @trigger 'failure', @id
    }

  onBeforeRender: ->
    @cleanUp()

  onYiiFormAfterValidate: (event, messages, errorAttributes) ->
    if @ui.twitterTabs.length and errorAttributes.length
      @selectTab($(errorAttributes[0].input).closest('.tab-pane'))

  onRender: ->
    progressOff(@$el)
    @ui.form.on 'afterValidate', _.bind(@onYiiFormAfterValidate, @)

    # TB tooltips
    @$('[data-toggle="tooltip"]')?.tooltip()

    Backbone.Radio.channel("widget").trigger "render", @
    return unless @ui.form.length
    if @ui.errortab.length
      @selectTab @ui.errortab

  selectTab: ($tabElement) ->
    id = $tabElement.prop 'id'
    link = @ui.twitterTabs.filter "[href=\"##{id}\"]:first"
    link.focus()
    link.trigger 'click'

  onReloadableChange: ->
    application.addJsReload @ui.form
    # because yiiActiveForm has timer, which will run & crash after destroy
    @ui.form.data('yiiActiveForm')?.submitting = true
    @ui.form.yiiActiveForm?('destroy')
    @ui.form.submit()

  twitterTabShow: (e)->
    e.preventDefault()
    $(e.target)?.tab 'show'

  onFormChange: ->
# nothing, needed by inlineForm

class application.Views.InlineForm extends AbstractForm

  getTemplate: ->
    return _.noop unless @templateData
    _.template "#{@templateData}"

  initialize: ->
    super
    @header = null

  onFormChange: ->
    pane = @ui.form.closest(".tab-pane")
    @header = pane.closest('.tab-content').parent().find("[href='##{pane.attr('id')}']")
    if @header.length && !$(".js-form-change-warning", @header).length
      title = Yii.t("app", "Form has been changed, do not forget to save !")
      content = "<span class=\"js-form-change-warning badge bg-warning badge-icon cursor-pointer p-1 ml-1\" title=\"#{title}\">
        <i class=\"fas fa-exclamation-triangle\"></i>
      </span>"
      @header.append content
    super

  onFormSubmit: ->
    @header?.find('.js-form-change-warning').remove()
    super

class application.Views.Form extends AbstractForm
  defaultDialogOptions: ->
    {
      title: '',
      modal: true,
      width: '50vw',
      buttons: [],
      actions: [],
      autoOpen: true,
      minHeight: 150,
      minWidth: 350,
      position: {
        my: 'top',
        at: 'top+5%',
      },
      classes: {
        "ui-dialog": "ui-corner-all",
        "ui-dialog-titlebar": "ui-corner-all bg-primary-700 bg-success-gradient",
      }
      show: {
        effect: 'scale'
      },
      hide: {
        effect: 'scale'
      },
      open: =>
        @dialogShown = true
        if @ui.errortab.length
          @selectTab(@ui.errortab)
      close: =>
        @destroy()
    }

  getTemplate: ->
    return _.noop unless @templateData

    _.template("
          <div class=\"popup-dialog\">#{@templateData}</div>
      ")


  initialize: (@options)->
    @dialogShown = false
    @options = $.extend @defaultDialogOptions(), @options
    @configureButtons()
    super

  configureButtons: ->
# configure default buttons
    for action in @options.actions
      switch action
        when 'save'
          @options.buttons.push @saveButton()
        when 'ok'
          @options.buttons.push @okButton()
        when 'remove'
          @options.buttons.push @removeButton()
        when 'close'
          @options.buttons.push @closeButton()
        else
          @options.buttons.push action
    delete @options.actions

  onRender: ->
    if @dialogShown
      @$el.dialog 'destroy'
      @options.show = null
      @options.hide = null
    @$el.dialog @options
    super

  onBeforeDestroy: ->
    super
    if @dialogShown
      @$el.dialog 'destroy'
      @dialogShown = false

  okButton: ->
    {
      title: Yii.t('app', 'Ok'),
      text: Yii.t('app', 'Ok'),
      class: 'btn btn-success',
      click: =>
        unless @ui.form.length
          @destroy()
          return
        @ui.form.submit()
    }

  saveButton: ->
    {
      title: Yii.t('app', 'Save'),
      text: Yii.t('app', 'Save'),
      class: 'btn btn-success',
      click: =>
        @ui.form.submit()
    }

  closeButton: ->
    {
      title: Yii.t('app', 'Close'),
      text: Yii.t('app', 'Close'),
      class: 'btn btn-primary',
      click: =>
        @destroy()
    }

  removeButton: ->
    {
      title: Yii.t('app', 'Remove'),
      text: Yii.t('app', 'Remove'),
      class: 'btn btn-danger',
      click: =>
        @ui.form.submit()
    }

application.toggleObject = (o)->
  o.toggleClass 'disabled js-disabled'

application.enableObject = (o)->
  o.removeClass 'disabled js-disabled'

application.disableObject = (o)->
  o.addClass 'disabled js-disabled'

application.confirmDialog = (event, dialogOptions) ->
  defaultOptions = {
    title: Yii.t("app", "Are you sure ?"),
  }

  bootbox.confirm _.extend defaultOptions, dialogOptions

application.addJsReload = ($element)->
  $element.append "<input type=\"hidden\" name=\"js-reload\" value=\"1\"/>"

$ ->
  $("body").on 'click', '.js-disabled', (e)->
    e.preventDefault()
    e.stopImmediatePropagation()

  reloadDocument = ->
    progressOn($("body"))
    location.reload()

  $(document).on "reload", reloadDocument


  # should be called with target context
  actionSuccess = ->
    progressOff($("body"))
    if @hasClass 'js-reload-document'
      reloadDocument()
    if @hasClass 'js-reload-parent'
      @trigger 'reload'


  $("body").on 'click', '.js-destroy', (e)->
    e.preventDefault()
    target = $(@)
    params = target.data('params')
    application.confirmDialog e, {
      message: params?.message || Yii.t("app", "Are you sure you want to remove record ?"),
      title: params?.title || undefined
      callback: (result) ->
        return unless result
        $.ajax {
          url: target.prop('href'),
          type: "DELETE",
          success: _.bind(actionSuccess, target)
        }
    }


  $("body").on 'click', '.js-command', (e)->
    e.preventDefault()
    target = $(@)
    commandFunction = ->
      progressOn $("body")
      $.post target.prop('href'), {}, _.bind(actionSuccess, target)

    params = target.data 'params'
    unless params?['requires-confirmation']
      commandFunction.apply e
      return

    application.confirmDialog e, _.extend {
      callback: (result) ->
        return unless result
        commandFunction.apply e
    }, params


  $("body").on 'click', '.js-dialog', (e)->
    target = $(@)
    map = {
      'js-dialog-ok': 'ok',
      'js-dialog-save': 'save',
      'js-dialog-remove': 'remove',
      'js-dialog-close': 'close',
    }

    buttons = []
    for klass, button of map
      if target.hasClass(klass)
        buttons.push button

    params = target.data('params') || {}
    params.title = params?.title || target.prop('title') || ''
    params = _.extend params, {
      url: target.prop('href'),
      actions: buttons,
    }
    e.preventDefault()

    form = new application.Views.Form(params)
    application.listenTo form, 'success', ->
      actionSuccess.call target
      form.destroy()
