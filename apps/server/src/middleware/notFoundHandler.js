/**
 * Express middleware returning a JSON 404 response for unmatched routes.
 *
 * @param {import('express').Request} req Incoming request object.
 * @param {import('express').Response} res Express response object.
 * @returns {void}
 */
function notFoundHandler(req, res) {
  res.status(404).json({ error: 'Nie znaleziono zasobu.' });
}

module.exports = {
  notFoundHandler,
};
