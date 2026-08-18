const SPREADSHEET_ID = '1dvYb5bhw9RjTiatW_4PWJd8KK1RK7HoItovO8Ge28AE';

function doPost(e) {
  try {
    const payload = JSON.parse(e.postData.contents);
    const expectedSecret = PropertiesService.getScriptProperties().getProperty('WEBHOOK_SECRET');

    if (!expectedSecret || payload.secret !== expectedSecret) {
      throw new Error('Unauthorized');
    }

    // AUTH-01 -> AUTH, GROUP-BAS-01 -> GROUP-BAS
    const sheetName = String(payload.testId).replace(/-\d+$/, '');
    const sheet = SpreadsheetApp.openById(SPREADSHEET_ID).getSheetByName(sheetName);
    if (!sheet) throw new Error(`Sheet not found: ${sheetName}`);

    const values = sheet.getDataRange().getDisplayValues();
    const testCell = findCell_(values, payload.testId);
    if (!testCell) throw new Error(`Test Case ID not found: ${payload.testId}`);

    // Module sheets use fixed result columns: R = Laptop, V = Screenshots 1.
    const statusColumn = 18;
    const testedAtColumn = 19;
    const screenshotColumn = 22;

    sheet.getRange(testCell.row, statusColumn).setValue(payload.status);
    sheet.getRange(testCell.row, testedAtColumn)
      .setValue(new Date())
      .setNumberFormat('dd/MM/yyyy HH:mm:ss');

    if (payload.screenshotBase64) {
      const blob = Utilities.newBlob(
        Utilities.base64Decode(payload.screenshotBase64),
        'image/png',
        payload.screenshotName,
      );
      const folder = getScreenshotFolder_();
      const file = folder.createFile(blob);
      const screenshotCell = sheet.getRange(testCell.row, screenshotColumn);
      screenshotCell.setValue(file.getUrl());

      sheet.getImages()
        .filter(image => {
          const anchor = image.getAnchorCell();
          return anchor.getRow() === testCell.row && anchor.getColumn() === screenshotColumn;
        })
        .forEach(image => image.remove());

      sheet.insertImage(blob, screenshotColumn, testCell.row).setWidth(240).setHeight(135);
      sheet.setRowHeight(testCell.row, 145);
      sheet.setColumnWidth(screenshotColumn, 250);
    }

    return json_({ ok: true, testId: payload.testId, status: payload.status });
  } catch (error) {
    return json_({ ok: false, error: error.message });
  }
}

function findCell_(values, expectedValue) {
  for (let row = 0; row < values.length; row++) {
    for (let column = 0; column < values[row].length; column++) {
      if (values[row][column].trim() === expectedValue) {
        return { row: row + 1, column: column + 1 };
      }
    }
  }
  return null;
}

function getScreenshotFolder_() {
  const name = 'Rentbas Playwright Screenshots';
  const existing = DriveApp.getFoldersByName(name);
  return existing.hasNext() ? existing.next() : DriveApp.createFolder(name);
}

function json_(value) {
  return ContentService.createTextOutput(JSON.stringify(value))
    .setMimeType(ContentService.MimeType.JSON);
}
