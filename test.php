import com.sun.jna.Library;
import com.sun.jna.Native;
import com.sun.jna.ptr.IntByReference;

interface HLLAPI extends Library {
    HLLAPI INSTANCE = Native.load("pcshll32", HLLAPI.class);
    void hllapi(IntByReference func, byte[] data, IntByReference len, IntByReference ret);
}

public class CursorPosition {
    public static void main(String[] args) {
        IntByReference func = new IntByReference(13); // Query cursor
        IntByReference len = new IntByReference(0);
        IntByReference ret = new IntByReference(0);
        
        HLLAPI.INSTANCE.hllapi(func, new byte[0], len, ret);
        
        if (ret.getValue() == 0) {
            int pos = len.getValue();
            int row = (pos / 80) + 1;
            int col = (pos % 80) + 1;
            System.out.println("Line: " + row + ", Column: " + col);
        } else {
            System.err.println("Error: " + ret.getValue());
        }
    }
}