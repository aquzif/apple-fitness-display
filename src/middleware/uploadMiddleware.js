const multer = require('multer');
const os = require('os');

const MAX_FILE_SIZE = 10 * 1024 * 1024 * 1024; // 10 GB

/**
 * Builds a configured Multer instance capable of handling large Apple Health exports.
 *
 * @returns {import('multer').Multer} Multer instance ready for use as Express middleware.
 */
function createUploadMiddleware() {
  return multer({
    storage: multer.diskStorage({
      destination: (req, file, cb) => cb(null, os.tmpdir()),
      filename: (req, file, cb) => {
        const safeName = file.originalname.replace(/[^a-zA-Z0-9_.-]/g, '_');
        cb(null, `${Date.now()}-${safeName}`);
      },
    }),
    limits: {
      fileSize: MAX_FILE_SIZE,
    },
  });
}

module.exports = {
  createUploadMiddleware,
  MAX_FILE_SIZE,
};
