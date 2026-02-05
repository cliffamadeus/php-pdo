const { app, BrowserWindow, shell } = require("electron");

function createWindow() {
  const win = new BrowserWindow({
    width: 1200,
    height: 800,
    webPreferences: {
      nodeIntegration: false,  // safer for remote websites
      contextIsolation: true,
    }
  });

  // Load your remote PHP site
  win.loadURL("https://ics-dev.io/test/index.php");

  // Optional: remove menu
  win.setMenuBarVisibility(false);

  // Optional: open external links in default browser
  win.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: "deny" };
  });
}

app.whenReady().then(createWindow);

// Quit when all windows closed (Windows/Linux)
app.on("window-all-closed", () => {
  if (process.platform !== "darwin") app.quit();
});

// macOS: create new window when dock icon clicked
app.on("activate", () => {
  if (BrowserWindow.getAllWindows().length === 0) createWindow();
});
