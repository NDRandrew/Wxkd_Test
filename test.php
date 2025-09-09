public class CursorPosition {
    static {
        // Try 64-bit first, then 32-bit
        try {
            System.loadLibrary("pcshll64");
        } catch (UnsatisfiedLinkError e) {
            System.loadLibrary("pcshll32");
        }
    }
    
    public static void main(String[] args) {
        int[] pos = new int[2];
        int result = hllapi(13, "", 0, pos); // Query cursor position
        
        if (result == 0) {
            int row = (pos[0] / 80) + 1;
            int col = (pos[0] % 80) + 1;
            System.out.println("Line: " + row + ", Column: " + col);
        } else {
            System.err.println("HLLAPI Error: " + result);
        }
    }
    
    private static native int hllapi(int func, String data, int len, int[] pos);
}