/**
 * HTTP клиент для AJAX запросов
 */
const ApiClient = {
	/**
	 * Базовый AJAX запрос
	 */
	request(endpoint, options = {}) {
		const {
			method = 'GET',
			data = null,
			timeout = 30000,
			processData = true,
			contentType = 'application/x-www-form-urlencoded; charset=UTF-8',
			params = {}
		} = options;

		return $.ajax({
			url: Utils.buildUrl(endpoint, params),
			type: method,
			data: data,
			timeout: timeout,
			processData: processData,
			contentType: contentType,
			dataType: 'json'
		});
	},

	/**
	 * GET запрос
	 */
	get(endpoint, params = {}) {
		return this.request(endpoint, { params });
	},

	/**
	 * POST запрос
	 */
	post(endpoint, data = {}) {
		return this.request(endpoint, { method: 'POST', data });
	},

	/**
	 * Загрузка файла
	 */
	upload(endpoint, formData) {
		return this.request(endpoint, {
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			timeout: 60000
		});
	}
};